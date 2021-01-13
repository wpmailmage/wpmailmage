<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;

class WooCommerceOrderStatusEvent extends AbstractEvent implements EventInterface
{
    private $_settings = [];

    public function __construct($args = [])
    {
        $this->_settings = $args;
        $this->register_fields();
    }

    public function register_fields()
    {

        $status_options = [];
        foreach (wc_get_order_statuses() as $id => $label) {
            $status_options[] = ['value' => $id, 'label' => $label];
        }

        $this->register_field('Order Status', 'order_status', [
            'type' => 'select',
            'options' => $status_options
        ]);
    }

    public function get_label()
    {
        return 'On WooCommerce Order Status Changed';
    }

    private function strip_wc_status_prefix($new_status)
    {
        return 'wc-' === substr($new_status, 0, 3) ? substr($new_status, 3) : $new_status;
    }

    public function install_listener()
    {
        foreach ($this->_settings as $settings) {
            $order_status = $this->strip_wc_status_prefix($settings['order_status']);
            add_action('woocommerce_order_status_' . trim($order_status), [$this, 'on_woocommerce_order_status_set'], 10, 2);
        }
    }

    public function on_woocommerce_order_status_set($order_id, $order)
    {
        $this->triggered(['wc_order' => $order_id]);
    }

    public function get_placeholders()
    {
        return ['wc_order'];
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
         * @var \WC_Order $order
         */
        $order = $event_data['wc_order'];
        $current_order_status = $order->get_status();

        foreach ($this->_settings as $settings) {
            $order_status = $this->strip_wc_status_prefix($settings['order_status']);
            if ($order_status === $current_order_status) {
                return true;
            }
        }

        return new \WP_Error("EWP_WOSE_1", "Order status has changed to: " . $current_order_status);
    }
}
