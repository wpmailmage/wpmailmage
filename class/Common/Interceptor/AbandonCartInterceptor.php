<?php

namespace EmailWP\Common\Interceptor;

use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\Properties\Properties;
use EmailWP\Container;

class AbandonCartInterceptor
{
    protected $_is_modified = false;

    public function __construct()
    {
        $actions = [
            'woocommerce_add_to_cart',
            'woocommerce_cart_item_removed',
            'woocommerce_cart_item_restored',
            'woocommerce_before_cart_item_quantity_zero',
            'woocommerce_after_cart_item_quantity_update'
        ];

        foreach ($actions as $action) {
            add_action($action, [$this, 'modified_cart']);
        }

        add_action('shutdown', [$this, 'save_cart']);
        add_action('woocommerce_checkout_create_order', [$this, 'sync_cart']);

        add_action('wp_login', function ($user_login, $user) {
            $old_session_id = WC()->session->get_customer_id();

            try {
                $automation_woocommerce_cart = new AutomationWoocommerceCart($old_session_id);
                $automation_woocommerce_cart->set_user_id($user->ID);
            } catch (\Exception $e) {
            }
        }, 10, 2);
    }

    /**
     * Store a flag to say the cart has been modified.
     *
     * @return void
     */
    public function modified_cart()
    {
        $this->_is_modified = true;
    }

    /**
     * Save cart only when modified
     *
     * @return void
     */
    public function save_cart()
    {
        if (is_admin()) {
            return;
        }

        if (!WC()->cart) {
            return;
        }

        if (!$this->_is_modified) {
            return;
        }

        $session_id = WC()->session->get_customer_id();
        $automation_woocommerce_cart = new AutomationWoocommerceCart($session_id);
        $automation_woocommerce_cart->set_cart(WC()->cart->get_cart_for_session());
        $automation_woocommerce_cart->save();
    }

    /**
     * Sync cart order with abandoned cart table
     *
     * @param \WC_Order $order
     * 
     * @return void
     */
    public function sync_cart($order)
    {
        $session_id = WC()->session->get_customer_id();

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        // Delete order from table as its not abandoned.
        $wpdb->delete($properties->table_automation_woocommerce_carts, ['session_id' => $session_id]);
    }
}
