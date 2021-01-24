<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;
use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\Properties\Properties;
use EmailWP\Container;

class WooCommerceAbandonedCartEvent extends AbstractEvent implements
    EventInterface
{
    public function __construct()
    {
        // install abandoned cart listener
        Container::getInstance()->maybeAddInterceptor('EmailWP\Common\Interceptor\AbandonCartInterceptor');

        add_action('wp_ajax_ewp_save_cart', [$this, 'save_cart']);
        add_action('wp_ajax_nopriv_ewp_save_cart', [$this, 'save_cart']);
    }

    public function install_js()
    {
        if (!is_checkout()) {
            return;
        }

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        wp_enqueue_script('ewp-wcac', $properties->js_url . 'woocommerce-capture-guest.js', ['jquery', 'woocommerce'], time());
        wp_localize_script('ewp-wcac', 'ewp', [
            'nonce' => wp_create_nonce('ewp_save_cart'), // $properties->get_rest_nonce(), //wp_create_nonce('wp_rest'),
            'ajax_url' => admin_url('admin-ajax.php') //rest_url('/' . $properties->rest_namespace . '/' . $properties->rest_version . '/cart'),
        ]);
    }

    /**
     * Save WC Cart Data
     */
    public function save_cart()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'ewp_save_cart')) {
            echo json_encode([
                'status' => 'E',
                'data' => 'Invalid Nonce'
            ]);
            exit;
        }

        $data = [
            'billing_first_name' => sanitize_text_field($_POST['billing_first_name']),
            'billing_last_name' => sanitize_text_field($_POST['billing_last_name']),
            'billing_email' => sanitize_email($_POST['billing_email']),
        ];

        $session_id = WC()->session->get_customer_id();
        $automation_woocommerce_cart = new AutomationWoocommerceCart($session_id);
        $automation_woocommerce_cart->set_cart(WC()->cart->get_cart_for_session());
        $automation_woocommerce_cart->set_data($data);
        $result = $automation_woocommerce_cart->save();
        echo json_encode([
            'status' => 'S',
            'data' => $result
        ]);
        exit;
    }

    /**
     * @param AutomationWoocommerceCart $cart
     */
    public function on_abandoned_cart($cart)
    {
        $this->triggered(['wc_cart' => $cart]);
    }

    public function install_listener()
    {
        add_action('wp_enqueue_scripts', [$this, 'install_js']);
        add_action('ewp/automation_woocommerce_cart/abandoned', [$this, 'on_abandoned_cart']);
    }

    public function get_label()
    {
        return 'On WooCommerce Abandoned Cart';
    }

    public function get_placeholders()
    {
        return ['wc_cart', 'general'];
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
        /**
         * @var AutomationWoocommerceCart $cart
         */
        $cart = $event_data['wc_cart'];
        $abandoned_items = $cart->get_item_ids();
        $abandoned_date = $cart->get_abanoned();

        // escape out as cart is no longer abandoned
        if (is_null($abandoned_date) || empty($abandoned_items)) {
            $this->set_log_message("Cart is no longer abandoned.");
            return false;
        }

        if ($cart->customer_has_ordered()) {
            $this->set_log_message("Order has been placed since cart abandoned.");
            return false;
        }

        return true;
    }
}
