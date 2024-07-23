<?php
/**
 * The template for displaying comments
 *
 * This is the template that displays the area of the page that contains both the current comments
 * and the comment form.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package cooklook
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() ) {
	return;
}
?>
	<?php if ( have_comments() ) : ?>
		<?php the_comments_navigation(); ?>
		<div class="comment-list">
			<?php
			wp_list_comments(
				array(
					'style'      => 'div',
					'short_ping' => true,
					'avatar_size'=> 60,
					'callback'   => 'commentsHTML5',
					'end-callback' => 'commentsHTML5_end',
					'reply_text' => __('Ответить', 'cooklook'),
					'per_page'   => 10,
					'reverse_top_level' => false, // или true, в зависимости от предпочтений
					'login_text' => __('Войдите или зарегистрируйтесь чтобы оставить комментарий', 'cooklook'),
					'format'     => 'html5',
					'max_depth'  => 3, // Установите максимальную глубину вложенности
				)
			);
			?>
		</div><!-- .comment-list -->
		
		<?php
		the_comments_navigation();
		// If comments are closed and there are comments, let's leave a little note, shall we?
		if ( ! comments_open() ) :
			?>
			<p class="no-comments"><?php esc_html_e( 'Comments are closed.', 'cooklook' ); ?></p>
			<?php
		endif;
	endif; // Check for have_comments().
	?>

	<div id="comment-success-message" class="success" style="display:none;"></div>
	<div id="comment-error-message" class="error" style="display:none;"></div>

	<?php
	comment_form(array(
		'title_reply' => '',
		'comment_notes_after' => '',
		'fields' => apply_filters('comment_form_default_fields', array(
			'author' => '<p class="comment-form-author">' . '<label for="author">' . __('Name') . '</label> ' . '<span class="required">*</span>' . '<input id="author" name="author" type="text" value="' . esc_attr($commenter['comment_author']) . '" size="30" /></p>',
			'email'  => '<p class="comment-form-email"><label for="email">' . __('Email') . '</label> ' . '<span class="required">*</span>' . '<input id="email" name="email" type="text" value="' . esc_attr($commenter['comment_author_email']) . '" size="30" /></p>',
		)),
		'comment_field' => '<p class="comment-form-comment"><label for="comment">' . _x('Comment', 'noun') . '</label><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true"></textarea></p>',
		'id_submit' => 'submit',
		'label_submit' => __('Submit'),
		'comment_form_top' => get_comment_id_fields() // Добавление скрытых полей здесь
	));
?>
<!-- #comments -->
