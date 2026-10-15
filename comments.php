<?php
/** @package cooklook */
if ( post_password_required() ) { return; }
?>
<div id="comments">
	<?php if ( have_comments() ) : ?>
		<?php the_comments_navigation(); ?>
		<div class="comment-list">
			<?php wp_list_comments( array(
				'style' => 'div', 'short_ping' => true, 'avatar_size' => 60,
				'callback' => 'commentsHTML5', 'end-callback' => 'commentsHTML5_end',
				'reply_text' => __( 'Ответить', 'cooklook' ), 'format' => 'html5',
			) ); ?>
		</div>
		<?php the_comments_navigation(); ?>
	<?php elseif ( comments_open() ) : ?>
		<div class="comment-list"></div>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="no-comments"><?php esc_html_e( 'Комментарии закрыты.', 'cooklook' ); ?></p>
	<?php endif; ?>
	<div id="comment-success-message" class="success" role="status" hidden></div>
	<div id="comment-error-message" class="error" role="alert" hidden></div>
	<?php comment_form( array( 'title_reply' => '', 'comment_notes_after' => '', 'label_submit' => __( 'Опубликовать', 'cooklook' ) ) ); ?>
</div>
