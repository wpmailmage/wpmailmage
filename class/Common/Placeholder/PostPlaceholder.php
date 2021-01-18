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
            'excerpt' => [$this, 'replace_excerpt'],
            'author' => [$this, 'replace_author'],
            'date' => [$this, 'replace_date'],
            'link' => [$this, 'replace_link'],
            'custom' => [$this, 'replace_custom'],
            // 'item' => [$this, 'replace_item'],
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

    /**
     * @param array $data
     * @return integer
     */
    public function replace_excerpt($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];

        return get_the_excerpt($post);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_author($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];
        return get_the_author_meta('display_name', $post->post_author);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_date($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];
        $format = isset($args['format']) ? $args['format'] : get_option('date_format') . ' ' . get_option('time_format');
        $time = isset($args['field']) && $args['field'] == 'created' ? $post->post_date : $post->post_modified;

        $wp_timezone = wp_timezone();
        $datetime = date_create_immutable_from_format('Y-m-d H:i:s', $time, $wp_timezone);

        return $datetime->format($format);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_link($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];
        return get_permalink($post);
    }

    /**
     * @param array $data
     * @return integer
     */
    public function replace_custom($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];
        $field = isset($args['field']) ? $args['field'] : false;

        if (empty($field)) {
            return '';
        }

        return get_post_meta($post->ID, $field, true);
    }


    public function replace_item($data, $args = [])
    {
        /**
         * @var \WP_Post $post
         */
        $post = $data[$this->get_id()];

        ob_start();
        return ob_get_clean();
    }
}
