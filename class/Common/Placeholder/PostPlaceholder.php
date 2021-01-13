<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\PlaceholderInterface;

class PostPlaceholder extends AbstractPlaceholder implements PlaceholderInterface
{
    public function get_id()
    {
        return 'post';
    }

    public function get_variables()
    {
        return [
            'id' => [$this, 'replace_id'],
            'title' => [$this, 'replace_title'],
        ];
    }

    public function save_data($data)
    {
        return intval($data);
    }

    public function load_data($data)
    {
        return get_post($data);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_id($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];

        return $post->ID;
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_title($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];

        return get_the_title($post);
    }
}
