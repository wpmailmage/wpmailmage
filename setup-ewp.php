<?php

function email_wp()
{
    global $ewp;

    if (!is_null($ewp)) {
        return $ewp;
    }

    $ewp = new EmailWP\Free\EmailWPFree();
    $ewp->register();

    return $ewp;
}

function ewp_loaded()
{
    if (function_exists(('email_wp'))) {
        email_wp();
    }
}
add_action('plugins_loaded', 'ewp_loaded');
