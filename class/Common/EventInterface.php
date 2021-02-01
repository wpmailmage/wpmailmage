<?php

namespace EmailWP\Common;

interface EventInterface
{
    /**
     * Get Event Fields
     *
     * @return array
     */
    public function get_fields();

    /**
     * Get Event Label
     *
     * @return string
     */
    public function get_label();

    /**
     * Get list of allowed placeholders
     *
     * @return string[]
     */
    public function get_placeholders();

    /**
     * Show schedule settings
     *
     * @return boolean
     */
    public function has_schedule();

    /**
     * Install Event Listener
     *
     * @return void
     */
    public function install_listener();
}
