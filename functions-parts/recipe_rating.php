<?php
defined( 'ABSPATH' ) || exit;

function get_user_vote_meta( $user_id, $recipe_id ) {
	$votes = get_user_meta( $user_id, 'user_votes', true );
	$vote = is_array( $votes ) && isset( $votes[ $recipe_id ] ) ? $votes[ $recipe_id ] : 'none';
	return in_array( $vote, array( 'like', 'dislike' ), true ) ? $vote : 'none';
}

function get_button_type( $recipe_id, $user_vote ) {
	return in_array( $user_vote, array( 'like', 'dislike' ), true ) ? $user_vote : '';
}

/** Repeated requests are idempotent. Removal only removes the matching vote. */
function cooklook_vote_transition( $previous, $requested, $likes, $dislikes ) {
	$previous = in_array( $previous, array( 'like', 'dislike' ), true ) ? $previous : 'none';
	$next = $previous;
	if ( in_array( $requested, array( 'like', 'dislike' ), true ) ) {
		$next = $requested;
	} elseif ( 'remove_' . $previous === $requested ) {
		$next = 'none';
	}
	return array(
		'user_vote' => $next,
		'likes' => max( 0, (int) $likes + (int) ( 'like' === $next ) - (int) ( 'like' === $previous ) ),
		'dislikes' => max( 0, (int) $dislikes + (int) ( 'dislike' === $next ) - (int) ( 'dislike' === $previous ) ),
	);
}

function cooklook_recipe_rating_data( $post_id ) {
	$likes = max( 0, (int) get_post_meta( $post_id, '_recipe_likes', true ) );
	$dislikes = max( 0, (int) get_post_meta( $post_id, '_recipe_dislikes', true ) );
	return array( 'likes' => $likes, 'dislikes' => $dislikes, 'rating' => calculate_rating( $likes, $dislikes ) );
}

function check_user_vote() {
	cooklook_verify_ajax_nonce( 'cooklook_rating' );
	$post_id = cooklook_ajax_recipe_id();
	$vote = get_user_vote_meta( get_current_user_id(), $post_id );
	wp_send_json_success( array( 'user_vote' => $vote, 'button_type' => get_button_type( $post_id, $vote ) ) );
}

function update_user_vote() {
	cooklook_verify_ajax_nonce( 'cooklook_rating' );
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Войдите, чтобы оценить рецепт.', 'cooklook' ), 401 );
	}
	$post_id = cooklook_ajax_recipe_id();
	$user_id = get_current_user_id();
	$requested = cooklook_request_text( $_POST, 'vote_type' );
	if ( ! in_array( $requested, array( 'like', 'dislike', 'remove_like', 'remove_dislike' ), true ) ) {
		wp_send_json_error( __( 'Недопустимый голос.', 'cooklook' ), 400 );
	}
	// Both the user's shared vote map and the recipe totals need serialization.
	$result = cooklook_with_lock( 'votes-user-' . $user_id, function () use ( $user_id, $post_id, $requested ) {
		return cooklook_with_lock( 'votes-recipe-' . $post_id, function () use ( $user_id, $post_id, $requested ) {
			wp_cache_delete( $user_id, 'user_meta' );
			wp_cache_delete( $post_id, 'post_meta' );
			$old_votes = get_user_meta( $user_id, 'user_votes', true );
			$votes = is_array( $old_votes ) ? $old_votes : array();
			$previous = get_user_vote_meta( $user_id, $post_id );
			$old = cooklook_recipe_rating_data( $post_id );
			$next = cooklook_vote_transition( $previous, $requested, $old['likes'], $old['dislikes'] );
			if ( $previous !== $next['user_vote'] ) {
				$votes[ $post_id ] = $next['user_vote'];
				if ( ! update_user_meta( $user_id, 'user_votes', $votes ) ) {
					return new WP_Error( 'save_failed', __( 'Не удалось сохранить голос.', 'cooklook' ) );
				}
				foreach ( array( 'likes', 'dislikes' ) as $key ) {
					if ( $old[ $key ] !== $next[ $key ] && ! update_post_meta( $post_id, '_recipe_' . $key, $next[ $key ] ) ) {
						update_user_meta( $user_id, 'user_votes', $old_votes );
						update_post_meta( $post_id, '_recipe_likes', $old['likes'] );
						update_post_meta( $post_id, '_recipe_dislikes', $old['dislikes'] );
						return new WP_Error( 'save_failed', __( 'Не удалось сохранить голос.', 'cooklook' ) );
					}
				}
			}
			$next['rating'] = calculate_rating( $next['likes'], $next['dislikes'] );
			return $next;
		} );
	} );
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( $result->get_error_message(), 'busy' === $result->get_error_code() ? 409 : 500 );
	}
	wp_send_json_success( $result );
}

function get_recipe_rating() {
	cooklook_verify_ajax_nonce( 'cooklook_rating' );
	wp_send_json_success( cooklook_recipe_rating_data( cooklook_ajax_recipe_id() ) );
}
foreach ( array( 'check_user_vote', 'update_user_vote', 'get_recipe_rating' ) as $cooklook_action ) {
	add_action( 'wp_ajax_' . $cooklook_action, $cooklook_action );
	add_action( 'wp_ajax_nopriv_' . $cooklook_action, $cooklook_action );
}
unset( $cooklook_action );
