<?php

namespace EmailWP\Common\Model;

class AutomationModel
{
    /**
     * @var int $id
     */
    protected $id;

    /**
     * @var string $name
     */
    protected $name;

    /**
     * @var string $name
     */
    protected $action;

    /**
     * @var array $name
     */
    protected $action_settings;

    /**
     * @var string $name
     */
    protected $event;

    /**
     * @var array $name
     */
    protected $event_settings;

    /**
     * @var boolean $disabled
     */
    protected $disabled;

    /**
     * @var array $delay
     */
    protected $delay;

    public function __construct($data = null)
    {
        $this->setup_data($data);
    }

    private function setup_data($data)
    {

        if (is_array($data)) {

            // $data = $this->sanitize($data);

            // fetch data from array
            $this->id = isset($data['id']) && intval($data['id']) > 0 ? intval($data['id']) : null;
            $this->name = isset($data['name']) ? sanitize_text_field($data['name']) : null;
            $this->load_data($data);
        } elseif (!is_null($data)) {

            $post = false;

            if ($data instanceof \WP_Post) {

                // fetch data from post
                $post = $data;
            } elseif (intval($data) > 0) {

                // fetch data from id
                $this->id = intval($data);
                $post = get_post($this->id);
            }

            if ($post && $post->post_type === EWP_POST_TYPE) {

                $json = maybe_unserialize($post->post_content, true);
                $this->id = $post->ID;
                $this->name = $post->post_title;

                $this->load_data($json);
            }
        }
    }

    public function save()
    {
        // Match what happens in wp-rest.
        remove_filter('content_save_pre', 'wp_filter_post_kses');

        $data = $this->data();
        if (isset($data['id'])) {
            unset($data['id']);
        }

        $postarr = [
            'post_title' => $this->name,
            'post_content' => serialize($this->data())
        ];

        if (is_null($this->id)) {
            $postarr['post_type'] = EWP_POST_TYPE;
            $postarr['post_status'] = 'publish';

            $result = wp_insert_post($postarr, true);
        } else {
            $postarr['ID'] = $this->id;
            $result = wp_update_post($postarr, true);
        }

        // Match what happens in wp-rest.
        add_filter('content_save_pre', 'wp_filter_post_kses');


        if (!is_wp_error($result)) {
            $this->setup_data($result);
        }

        return $result;
    }

    public function load_data($data)
    {
        $this->event = isset($data['event'], $data['event']['id']) ? sanitize_text_field($data['event']['id']) : null;
        $this->event_settings = isset($data['event'], $data['event']['settings']) ? $data['event']['settings'] : null;

        $this->action = isset($data['action'], $data['action']['id']) ? sanitize_text_field($data['action']['id']) : null;
        $this->action_settings = isset($data['action'], $data['action']['settings']) ? $data['action']['settings'] : null;

        $this->disabled = isset($data['disabled']) && $data['disabled'] === 'yes' ? true : false;

        $this->delay = isset($data['delay']) ? $data['delay'] : ['unit' => null, 'interval' => null];
    }

    public function delete()
    {
        if (get_post_type($this->get_id()) === EWP_POST_TYPE) {
            return wp_delete_post($this->get_id(), true);
        }
        return false;
    }

    public function get_id()
    {
        return $this->id;
    }

    public function get_name()
    {
        return $this->name;
    }

    public function set_name($name)
    {
        $this->name = $name;
    }

    public function get_action()
    {
        return $this->action;
    }

    public function set_action($action, $settings = null)
    {
        $this->action = $action;

        if (!is_null($settings)) {
            $this->set_action_settings($settings);
        }
    }

    public function get_action_settings()
    {
        return $this->action_settings;
    }

    public function set_action_settings($settings)
    {
        $this->action_settings = $settings;
    }

    public function get_action_setting($key)
    {
        return isset($this->action_settings[$key]) ? $this->action_settings[$key] : null;
    }

    public function set_action_setting($key, $value)
    {
        $this->action_settings[$key] = $value;
    }

    public function get_event()
    {
        return $this->event;
    }

    public function set_event($event, $settings = null)
    {
        $this->event = $event;

        if (!is_null($settings)) {
            $this->set_event_settings($settings);
        }
    }

    public function get_event_settings()
    {
        return $this->event_settings;
    }

    public function set_event_settings($settings)
    {
        $this->event_settings = $settings;
    }

    public function get_event_setting($key)
    {
        return isset($this->event_settings[$key]) ? $this->event_settings[$key] : null;
    }

    public function set_event_setting($key, $value)
    {
        $this->event_settings[$key] = $value;
    }

    public function is_disabled()
    {
        return $this->disabled;
    }

    public function disable($disabled = true)
    {
        if ($disabled === true || strtolower($disabled) === 'yes') {
            $this->disabled = true;
        } else {
            $this->disabled = false;
        }
    }

    public function get_delay()
    {
        $interval = isset($this->delay['interval'], $this->delay['unit']) ? intval($this->delay['interval']) : 0;
        if ($interval <= 0) {
            return 0;
        }

        $unit = $this->delay['unit'];
        $delay = null;

        switch ($unit) {
            case 'day':
            case 'days':
                $delay = DAY_IN_SECONDS * $interval;
                break;
            case 'hour':
            case 'hours':
                $delay = HOUR_IN_SECONDS * $interval;
                break;
            case 'minute':
            case 'minutes':
                $delay = MINUTE_IN_SECONDS * $interval;
                break;
            case 'second':
            case 'seconds':
                $delay = $interval;
                break;
        }

        return $delay;
    }

    public function set_delay($interval, $unit = 'day')
    {
        $interval = intval($interval);

        switch ($unit) {
            case 'day':
            case 'days':
                $unit = 'day';
                break;
            case 'hour':
            case 'hours':
                $unit = 'hour';
                break;
            case 'minute':
            case 'minutes':
                $unit = 'minute';
                break;
            case 'second':
            case 'seconds':
                $unit = 'second';
                break;
            default:
                $unit = false;
                break;
        }

        if ($unit && $interval > 0) {
            $this->delay = [
                'interval' => $interval,
                'unit' => $unit
            ];
        } else {
            $this->delay = [];
        }
    }

    public function data()
    {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'delay' => $this->delay,
            'disabled' => $this->is_disabled() ? 'yes' : 'no',
            'event' => [
                'id' => $this->get_event(),
                'settings' => (array)$this->get_event_settings()
            ],
            'action' => [
                'id' => $this->get_action(),
                'settings' => (array)$this->get_action_settings()
            ]
        ];
    }
}
