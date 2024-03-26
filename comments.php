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
					'callback'          => 'commentsHTML5', //функция в template-functions.php
					'reply_text' => __('Ответить','cooklook'),
					'per_page'   => 10,
					'reverse_top_level' => true,
					'login_text' => __('Войдите или зарегистрируйтесь чтобы оставить комментарий', 'cooklook'),
					'format' => 'html5',
					'max_depth'         => ''
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

	comment_form(array(
		'title_reply'=>'')
		
	);
	?>

<!-- #comments -->
