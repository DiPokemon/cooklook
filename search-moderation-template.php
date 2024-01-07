<?php
/*
* Страница модерации поисковых запросов. Для защиты от спама
*/
$pending_requests = $args['pending_requests'];
?>

<div class="wrap">
    <h1>Модерация поисковых запросов</h1>
    <?php if($pending_requests) : ?>
        <div class="search_moderation">
            <?php foreach ($pending_requests as $request) : ?>
                <div class="search_moderation-item">
                    <span class="search_value"><?= esc_html($request->query) ?></span>
                    <a href="<?= admin_url('admin-post.php?action=approve_search&request_id=' . $request->id) ?>" class="search_moderation-approve">Одобрить</a>
                    <a href="<?= admin_url('admin-post.php?action=reject_search&request_id=' . $request->id) ?>" class="search_moderation-reject">Отклонить</a>
                </div>
            <?php endforeach ?>
        </div>
    
    <?php else : ?>
        <p>На данный момент запросы на модерацию отсутствуют.</p>
    <?php endif ?>    
</div>

