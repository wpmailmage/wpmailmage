<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;

class WooCommerceSubscriptionOrderStatusEvent extends AbstractEvent implements EventInterface
{
    private $_settings = [];

    public function __construct($args = [])
    {
        $this->_settings = $args;
        $this->register_fields();
    }

    public function register_fields()
    {

        $status_options = [
            ['value' => '*', 'label' => 'Any']
        ];
        foreach (wcs_get_subscription_statuses() as $id => $label) {
            $status_options[] = ['value' => $id, 'label' => $label];
        }

        $this->register_field('Status From', 'subscription_status_from', [
            'type' => 'select',
            'options' => $status_options
        ]);
        $this->register_field('Status To', 'subscription_status_to', [
            'type' => 'select',
            'options' => $status_options
        ]);
    }

    public function get_label()
    {
        return 'On WooCommerce Subscription Status Changed';
    }

    private function strip_wc_status_prefix($new_status)
    {
        return 'wc-' === substr($new_status, 0, 3) ? substr($new_status, 3) : $new_status;
    }

    public function install_listener()
    {
        add_action('woocommerce_subscription_status_updated', [$this, 'on_woocommerce_subscription_status_set'], 10, 3);
    }

    /**
     * @param \WC_Subscription $subscription
     */
    public function on_woocommerce_subscription_status_set($subscription, $status_to, $status_from)
    {
        foreach ($this->_settings as $settings) {

            // from status
            $check_from = $settings['subscription_status_from'];
            if ($check_from != '*') {
                $check_from = $this->strip_wc_status_prefix($check_from);
            }

            if ($check_from != '*' && $check_from != $status_from) {
                continue;
            }

            // to status
            $check_to = $settings['subscription_status_to'];
            if ($check_to != '*') {
                $check_to = $this->strip_wc_status_prefix($check_to);
            }

            if ($check_to != '*' && $check_to != $status_to) {
                continue;
            }

            $this->triggered(['wc_order' => $subscription->get_id()]);
            return true;
        }

        return false;
    }

    public function get_placeholders()
    {
        return ['wc_order', 'general'];
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
         * @var \WC_Subscription $order
         */
        $order = $event_data['wc_order'];
        $current_order_status = $order->get_status();

        foreach ($this->_settings as $settings) {
            $order_status = $this->strip_wc_status_prefix($settings['subscription_status_to']);
            if ($order_status === $current_order_status) {
                return true;
            }
        }

        $this->set_log_message("Subscription status has changed to: " . $current_order_status);
        return false;
    }
}
