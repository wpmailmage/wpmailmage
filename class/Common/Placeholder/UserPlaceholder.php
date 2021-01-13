<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;

class UserPlaceholder extends AbstractPlaceholder implements PlaceholderInterface
{
    public function get_id()
    {
        return 'user';
    }

    public function get_variables()
    {
        return [
            'id' => [$this, 'replace_id'],
            'email' => [$this, 'replace_email'],
        ];
    }

    public function save_data($data)
    {
        return intval($data);
    }

    public function load_data($data)
    {
        return get_user_by('id', $data);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_id($data, $args = [])
    {
        /**
         * @var \WP_User $data
         */
        $user = $data[$this->get_id()];
        return $user->ID;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_email($data, $args = [])
    {
        /**
         * @var \WP_User $data
         */
        $user = $data[$this->get_id()];
        return $user->user_email;
    }
}
