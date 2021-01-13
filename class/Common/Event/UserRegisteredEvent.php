<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;

class UserRegisteredEvent extends AbstractEvent implements EventInterface
{

    public function get_fields()
    {
        return [];
    }

    public function get_label()
    {
        return 'On User Registered';
    }

    public function install_listener()
    {
        add_action('user_register', 'on_user_register');
    }

    public function on_user_register($user_id)
    {
        $this->triggered(['user' => $user_id]);
    }

    public function get_placeholders()
    {
        return ['user'];
    }

    /**
     * Verification check before action is ran.
     *
     * @param array $event_data
     * 
     * @return \WP_Error|true
     */
    public function verified($event_data = [])
    {
        return true;
    }
}
