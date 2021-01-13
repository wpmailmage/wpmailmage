<?php

namespace EmailWP\Common\Placeholder;

use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Container;

abstract class AbstractWooCommercePlaceholder extends AbstractPlaceholder
{
    /**
     * @param array $data
     * @return integer
     */
    public function replace_email($data, $args = [])
    {
        /**
         * @var \WC_Order $order
         */
        $order = $data[$this->get_id()];
        return $order->get_billing_email();
    }

    /**
     * @param array $data
     * @param array $args
     */
    public function replace_coupon($data, $args)
    {
        /**
         * @var \WC_Order $data
         */
        $order = $data[$this->get_id()];

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $coupon_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='shop_coupon' AND post_status='private' AND post_title=%s", $args['template']));
        if (!$coupon_id) {
            return '';
        }

        // Clone coupon
        $post = get_post($coupon_id);
        $new_coupon_id = $this->duplicate_coupon($post);

        // add restictions
        update_post_meta($new_coupon_id, 'usage_limit', 1);
        update_post_meta($new_coupon_id, 'customer_email', [$order->get_billing_email()]);
        update_post_meta($new_coupon_id, 'date_expires', strtotime('+ 30 days'));

        // cleanup if email fails to send.
        $this->add_cleanup_method(function () use ($new_coupon_id) {
            wp_delete_post($new_coupon_id, true);
        });

        $coupon_code = wc_get_coupon_code_by_id($new_coupon_id);

        // TODO: add to 
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $properties = Container::getInstance()->get('properties');
        $wpdb->insert($properties->table_automation_queue_activity, [
            'queue_id' => intval($data['queue_id']),
            'type' => 'generate::wc_coupon',
            'data' => $coupon_code,
            'created' => current_time('mysql')
        ]);

        return $coupon_code;
    }

    private function duplicate_coupon($post)
    {
        global $wpdb;

        $new_post_author    = wp_get_current_user();
        $new_post_date      = current_time('mysql');
        $new_post_date_gmt  = get_gmt_from_date($new_post_date);


        $post_parent = $post->post_parent;
        $post_status = 'publish';
        $post_title  = 'EWP-' . md5(microtime());

        // Insert the new template in the post table
        $wpdb->insert(
            $wpdb->posts,
            array(
                'post_author'               => $new_post_author->ID,
                'post_date'                 => $new_post_date,
                'post_date_gmt'             => $new_post_date_gmt,
                'post_content'              => $post->post_content,
                'post_content_filtered'     => $post->post_content_filtered,
                'post_title'                => wc_sanitize_coupon_code($post_title),
                'post_name'                 => sanitize_title($post_title),
                'post_excerpt'              => $post->post_excerpt,
                'post_status'               => $post_status,
                'post_type'                 => $post->post_type,
                'comment_status'            => $post->comment_status,
                'ping_status'               => $post->ping_status,
                'post_password'             => $post->post_password,
                'to_ping'                   => $post->to_ping,
                'pinged'                    => $post->pinged,
                'post_modified'             => $new_post_date,
                'post_modified_gmt'         => $new_post_date_gmt,
                'post_parent'               => $post_parent,
                'menu_order'                => $post->menu_order,
                'post_mime_type'            => $post->post_mime_type
            )
        );

        $old_coupon_id = $post->ID;
        $new_coupon_id = $wpdb->insert_id;

        // Copy the meta information
        $this->duplicate_post_meta($old_coupon_id, $new_coupon_id);

        // Clear cache
        clean_post_cache($new_coupon_id);

        return $new_coupon_id;
    }

    private function duplicate_post_meta($id, $new_id)
    {
        global $wpdb;

        $sql     = $wpdb->prepare("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d", absint($id));

        $post_meta = $wpdb->get_results($sql);

        if (sizeof($post_meta)) {
            $sql_query_sel = array();
            $sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";

            foreach ($post_meta as $post_meta_row) {
                $meta_key = $post_meta_row->meta_key;
                $meta_value = $post_meta_row->meta_value;

                // Reset the Usage Count when copying.
                if ('usage_count' === $meta_key) {
                    $meta_value = 0;
                }

                // Reset the Used By field when copying.
                if ('_used_by' === $meta_key) {
                    continue;
                }

                $sql_query_sel[] = $wpdb->prepare("SELECT %d, %s, %s", $new_id, $meta_key, $meta_value);
            }

            $sql_query .= implode(" UNION ALL ", $sql_query_sel);
            $wpdb->query($sql_query);
        }
    }

    public function replace_first_name($data)
    {
        /**
         * @var \WC_Order|AutomationWoocommerceCart $order
         */
        $order = $data[$this->get_id()];
        return $order->get_billing_first_name();
    }
    public function replace_last_name($data)
    {
        /**
         * @var \WC_Order|AutomationWoocommerceCart $order
         */
        $order = $data[$this->get_id()];
        return $order->get_billing_last_name();
    }
    public function replace_full_name($data)
    {
        /**
         * @var \WC_Order|AutomationWoocommerceCart $order
         */
        $order = $data[$this->get_id()];
        return $order->get_formatted_billing_full_name();
    }
}
