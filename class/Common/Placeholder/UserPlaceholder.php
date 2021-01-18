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
            'username' => [$this, 'replace_username'],
            'full_name' => [$this, 'replace_full_name'],
            'first_name' => [$this, 'replace_first_name'],
            'last_name' => [$this, 'replace_last_name'],
            'display_name' => [$this, 'replace_display_name'],
            'custom' => [$this, 'replace_custom']
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
         * @var \WP_User $user
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
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->user_email;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_display_name($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->display_name;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_username($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->user_login;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_full_name($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->first_name . ' ' . $user->last_name;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_first_name($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->first_name;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_last_name($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        return $user->last_name;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_custom($data, $args = [])
    {
        /**
         * @var \WP_User $user
         */
        $user = $data[$this->get_id()];
        $field = isset($args['field']) ? $args['field'] : false;

        if (empty($field)) {
            return '';
        }

        return get_user_meta($user->ID, $field, true);
    }
}
