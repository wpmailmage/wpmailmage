<?php

namespace EmailWP\Common\Analytics;

use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Container;

class WooCommerceTracker
{
    protected $_cookie_key;

    public function __construct($cookie_key)
    {
        $this->_cookie_key = $cookie_key;
        add_action('comment_post', [$this, 'on_comment_posted']);
        add_action('woocommerce_checkout_update_order_meta', [$this, 'on_new_order']);

        add_action('woocommerce_order_status_processing', [$this, 'process_paid_order']);
        add_action('woocommerce_order_status_completed', [$this, 'process_paid_order']);

        if (!is_admin()) {
            add_filter('query_vars', [$this, 'register_query_vars']);
            add_action('wp', [$this, 'check_query_vars']);
        }
    }

    /**
     * Keep track of wc review.
     *
     * @param int $comment_id Comment ID.
     */
    public function on_comment_posted($comment_id)
    {
        if (isset($_COOKIE[$this->_cookie_key]) && isset($_POST['comment_post_ID']) && 'product' === get_post_type(absint($_POST['comment_post_ID']))) { // WPCS: input var ok, CSRF ok.

            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;

            $properties = Container::getInstance()->get('properties');
            $wpdb->insert($properties->table_automation_queue_activity, [
                'queue_id' => intval($_COOKIE[$this->_cookie_key]),
                'type' => 'wc_review',
                'data' => serialize([
                    'comment_id' => $comment_id,
                    'product_id' => absint($_POST['comment_post_ID'])
                ]),
                'created' => current_time('mysql')
            ]);
        }
    }

    /**
     * Add referral session to new woocommerce orders
     * 
     * @param int $order_id
     */
    public function on_new_order($order_id)
    {
        if ($order_id && isset($_COOKIE[$this->_cookie_key])) {
            update_post_meta($order_id, '_ewp_referral_session', sanitize_text_field($_COOKIE['ewp_referral_session']));
        }

        $is_abandoned_cart = WC()->session->get('ewp_abandoned_cart');
        if (intval($is_abandoned_cart) > 0) {
            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;
            $properties = Container::getInstance()->get('properties');

            $wpdb->insert($properties->table_automation_queue_activity, [
                'queue_id' => intval($is_abandoned_cart),
                'type' => 'recovered::wc_cart',
                'data' => $order_id,
                'created' => current_time('mysql')
            ]);

            WC()->session->set('ewp_abandoned_cart', 0);
        }
    }

    /**
     * Check for referral session on new woocommerce orders
     * 
     * @param int $order_id
     */
    public function process_paid_order($order_id)
    {
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $properties = Container::getInstance()->get('properties');

        // Track coupon codes used.
        $order = wc_get_order($order_id);
        $coupons = $order->get_coupon_codes();
        if ($coupons) {
            foreach ($coupons as $code) {
                $used_coupon_queue_id = $wpdb->get_var("SELECT `queue_id` FROM {$properties->table_automation_queue_activity} WHERE `type`='generate::wc_coupon' AND `data`='" . $code . "' LIMIT 1");
                if ($used_coupon_queue_id) {
                    $wpdb->insert($properties->table_automation_queue_activity, [
                        'queue_id' => $used_coupon_queue_id,
                        'type' => 'used::wc_coupon',
                        'data' => $code,
                        'created' => current_time('mysql')
                    ]);
                }
            }
        }

        // Track order
        $queue_id = get_post_meta($order_id, '_ewp_referral_session', true);
        if ($queue_id) {
            $wpdb->insert($properties->table_automation_queue_activity, [
                'queue_id' => $queue_id,
                'type' => 'wc_order',
                'data' => $order_id,
                'created' => current_time('mysql')
            ]);
        }
    }

    public function get_charts()
    {
        return [];
    }

    public function register_query_vars($query_vars)
    {
        $query_vars[] = 'ewp_cart';
        return $query_vars;
    }

    public function check_query_vars()
    {
        $ref_session = get_query_var('ewp_ref_session', '');
        $cart_session = get_query_var('ewp_cart', '');
        if (!$cart_session || !$ref_session) {
            return;
        }

        $automation_woocommerce_cart = new AutomationWoocommerceCart($cart_session);
        $automation_woocommerce_cart->restore_cart();
        WC()->session->set('ewp_abandoned_cart', intval($ref_session));

        // force redirect to remove query var from string
        wp_redirect(remove_query_arg(['ewp_ref_session', 'ewp_cart']));
        exit;
    }
}
