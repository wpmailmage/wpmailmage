<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\Placeholder\PlaceholderManager;

class EventManager
{
    /**
     * @var EventHandler $event_handler
     */
    protected $event_handler;

    /**
     * @var PlaceholderManager
     */
    public $placeholder_manager;

    private $_events = [];

    public function __construct($event_handler, $placeholder_manager)
    {
        $this->event_handler = $event_handler;
        $this->placeholder_manager = $placeholder_manager;
        $this->_events = $this->get_events();
    }

    public function get_events()
    {

        $events = $this->event_handler->run('events.register', [[]]);

        // Register core events, unable to be overridden
        $events = array_merge($events, [
            'post.order_status' => PostStatusChangeEvent::class,
            'user.registered' => UserRegisteredEvent::class,
            'scheduled' => ScheduledEvent::class
        ]);

        if (class_exists('WooCommerce')) {
            $events = array_merge($events, [
                'woocommerce.order_status' => WooCommerceOrderStatusEvent::class,
                'woocommerce.abandoned_cart' => WooCommerceAbandonedCartEvent::class
            ]);
        }

        if (class_exists('WC_Subscription')) {
            $events = array_merge($events, [
                'woocommerce_subscription.order_status' => WooCommerceSubscriptionOrderStatusEvent::class,
            ]);
        }

        return $events;
    }

    public function get_event($id)
    {
        if (!isset($this->_events[$id])) {
            return new \WP_Error('EWP_AM_1', 'Unable to locate event: ' . $id);
        }

        return $this->_events[$id];
    }

    public function generate_event_data($data)
    {
        $output = [];

        if (is_user_logged_in() && !isset($data['user'])) {
            $data['user'] = get_current_user_id();
        }

        foreach ($data as $id => $data) {

            if ($this->placeholder_manager->has_placeholder($id)) {
                $output[$id] = $this->placeholder_manager->get_placeholder($id)->save_data($data);
            }
        }

        return $output;
    }

    public function load_event($id, $settings = [])
    {
        $event = $this->get_event($id);
        if (is_wp_error($event)) {
            return $event;
        }

        return new $event($settings);
    }

    public function load_event_data($event_data)
    {
        if (is_serialized($event_data)) {
            $event_data = unserialize($event_data);
        }

        $output = [];
        $core_args = ['queue_id', 'automation_id', '_action'];

        foreach ($event_data as $id => $data) {

            if (in_array($id, $core_args)) {
                $output[$id] = $data;
                continue;
            }

            if ($this->placeholder_manager->has_placeholder($id)) {
                $output[$id] = $this->placeholder_manager->get_placeholder($id)->load_data($data);
            }
        }



        return $output;
    }
}
