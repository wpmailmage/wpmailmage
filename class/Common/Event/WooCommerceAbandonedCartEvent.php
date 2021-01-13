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
            'nonce' => wp_create_nonce('wp_rest'), // $properties->get_rest_nonce(), //wp_create_nonce('wp_rest'),
            'ajax_url' => rest_url('/' . $properties->rest_namespace . '/' . $properties->rest_version . '/cart'),
        ]);
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
        return ['wc_cart'];
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
            return new \WP_Error("EWP_WACE_03", "Cart is no longer abandoned.");
        }

        $query = new \WC_Order_Query([
            'date_created' => '>' . $abandoned_date,
            'limit' => -1
        ]);
        $query->set('customer', $cart->get_billing_email());
        /**
         * @var \WC_Order[] $orders
         */
        $orders = $query->get_orders();
        if (!empty($orders)) {
            foreach ($orders as $order) {

                if (empty($abandoned_items)) {
                    break;
                }

                /**
                 * @var \WC_Order_Item_Product[] $items
                 */
                $items = $order->get_items();
                if (empty($items)) {
                    continue;
                }

                foreach ($items as $item) {

                    $product_id = $item->get_product_id();

                    $variation_id = 0;
                    if ($item->is_type('variable')) {
                        $variation_id = $item->get_variation_id();
                    }

                    $key = $product_id . '-' . $variation_id;
                    if (isset($abandoned_items[$key])) {
                        unset($abandoned_items[$key]);
                    }
                }
            }
        }

        // have purchased all items
        if (empty($abandoned_items)) {
            return new \WP_Error("EWP_WACE_01", "All Items have been purchased since cart abandonment.");
        }

        // have purchased some items
        if (count($abandoned_items) < count($cart->get_item_ids())) {
            return new \WP_Error("EWP_WACE_02", "Some Items have been purchased since cart abandonment.");
        }

        return true;
    }
}
