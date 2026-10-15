<?php
defined( 'ABSPATH' ) || exit;
$pending_requests = $args['pending_requests'];
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Модерация поисковых запросов', 'cooklook' ); ?></h1>
	<?php if ( $pending_requests ) : ?>
	<div class="search_moderation">
		<?php foreach ( $pending_requests as $request ) : ?>
		<div class="search_moderation-item">
			<span class="search_value"><?php echo esc_html( $request->query ); ?></span>
			<?php foreach ( array( 'approve_search' => 'Одобрить', 'reject_search' => 'Отклонить' ) as $action => $label ) : ?>
			<a class="button" href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => $action, 'request_id' => $request->id ), admin_url( 'admin-post.php' ) ), 'cooklook_moderate_search_' . $request->id ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
	</div>
	<?php else : ?><p><?php esc_html_e( 'Нет запросов для модерации.', 'cooklook' ); ?></p><?php endif; ?>
	<?php echo paginate_links( array(
		'base' => add_query_arg( array( 'page' => 'search-moderation', 'paged' => '%#%' ), admin_url( 'admin.php' ) ),
		'format' => '', 'current' => $args['page'], 'total' => $args['pages'],
	) ); ?>
</div>
