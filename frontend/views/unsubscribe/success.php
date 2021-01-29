<?php $view_manager->view('unsubscribe/layout/header'); ?>
<div class="ewp-unsubscribe-content">
    <h1>Unsubscribe Successful</h1>
    <p>You will no longer recieve email marketing from this list.</p>
    <a href="<?= site_url(); ?>" class="ewp-unsubscribe-btn">Visit <?= get_bloginfo('name'); ?></a>
</div>
<?php $view_manager->view('unsubscribe/layout/footer'); ?>