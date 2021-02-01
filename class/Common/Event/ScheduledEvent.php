<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;

class ScheduledEvent extends AbstractEvent implements
    EventInterface
{
    public function __construct()
    {
        $this->register_fields();
    }

    public function register_fields()
    {
        $this->register_field('Schedule', 'schedule', [
            'type' => 'schedule',
        ]);
    }

    public function install_listener()
    {
    }

    public function verified($event_data = [])
    {
        return true;
    }

    public function get_label()
    {
        return 'On Schedule';
    }

    public function get_placeholders()
    {
        return [];
    }

    /**
     * Show schedule settings
     *
     * @return boolean
     */
    public function has_schedule()
    {
        return false;
    }
}
