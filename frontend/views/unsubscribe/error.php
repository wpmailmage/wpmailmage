<?php $view_manager->view('unsubscribe/layout/header'); ?>
<div class="ewp-unsubscribe-content">
    <h1>Unsubscribe Unsuccessful</h1>
    <p>Sorry an error has occured when trying to unsubscribe your email address.</p>
    <a href="mailto:<?= get_bloginfo('admin_email'); ?>" class="ewp-unsubscribe-btn">Email <?= get_bloginfo('name'); ?></a>
</div>
<?php $view_manager->view('unsubscribe/layout/footer'); ?>