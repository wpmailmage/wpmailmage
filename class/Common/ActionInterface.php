<?php

namespace EmailWP\Common;

interface ActionInterface
{
    /**
     * Triggers the action to run
     *
     * @return true|\WP_Error
     */
    function run($event_data = []);
}
