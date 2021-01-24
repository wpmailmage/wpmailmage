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

    /**
     * @var array $schedule
     */
    protected $schedule;

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

        $this->disabled = isset($data['disabled']) && $data['disabled'] === 'no' ? false : true;

        $this->schedule = isset($data['schedule']) ? $data['schedule'] : [
            'type' => 'now',
            'delay' => [
                'unit' => null,
                'interval' => null
            ],
            'schedule' => [
                'unit' => '',
                'day' => null,
                'hour' => null
            ]
        ];
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

    public function calculate_scheduled_time($schedule, $day = 0, $hour = 0, $current_time = null)
    {
        $minute_padded = '00';
        $hour_padded = str_pad($hour, 2, 0, STR_PAD_LEFT);
        $day_padded = str_pad($day, 2, 0, STR_PAD_LEFT);
        $time_offset = time() - current_time('timestamp');
        $current_time = !is_null($current_time) ? $current_time : time();
        $scheduled_time = false;

        switch ($schedule) {
            case 'month':
                // 1-31

                // 31st of feb, should = 28/29
                if (date('t', $current_time) < $day) {
                    $day_padded = str_pad(date('t', $current_time), 2, 0, STR_PAD_LEFT);
                }

                $scheduled_time = $time_offset + strtotime(date('Y-m-' . $day_padded . ' ' . $hour_padded . ':' . $minute_padded . ':00', $current_time));

                if ($scheduled_time < $current_time) {

                    // 31st of feb, should = 28/29
                    $future_time = strtotime('+28 days', $current_time); // 28 days is the shortest month, adding + 1 month can skip feb
                    if (date('t', $future_time) < $day) {
                        $day_padded = str_pad(date('t', $future_time), 2, 0, STR_PAD_LEFT);
                    }

                    $scheduled_time = $time_offset + strtotime(date('Y-m-' . $day_padded . ' ' . $hour_padded . ':' . $minute_padded . ':00', $future_time));
                }
                break;
            case 'week':
                // day 0-6 : 0 = SUNDAY
                $day_str = '';
                switch (intval($day)) {
                    case 0:
                        $day_str =  'sunday';
                        break;
                    case 1:
                        $day_str =  'monday';
                        break;
                    case 2:
                        $day_str =  'tuesday';
                        break;
                    case 3:
                        $day_str =  'wednesday';
                        break;
                    case 4:
                        $day_str =  'thursday';
                        break;
                    case 5:
                        $day_str =  'friday';
                        break;
                    case 6:
                        $day_str =  'saturday';
                        break;
                }
                $scheduled_time = $time_offset + strtotime(date('Y-m-d ' . $hour_padded . ':' . $minute_padded . ':00', strtotime('next ' . $day_str, $current_time)));
                if ($scheduled_time - WEEK_IN_SECONDS > $current_time) {
                    $scheduled_time -= WEEK_IN_SECONDS;
                }
                break;
            case 'day':
                $scheduled_time = $time_offset + strtotime(date('Y-m-d ' . $hour_padded . ':' . $minute_padded . ':00', $current_time));
                if ($scheduled_time <= $current_time) {
                    $scheduled_time += DAY_IN_SECONDS;
                }
                break;
            case 'hour':
                $scheduled_time = strtotime(date('Y-m-d H:' . $minute_padded . ':00', $current_time));
                if ($scheduled_time <= $current_time) {
                    $scheduled_time += HOUR_IN_SECONDS;
                }
                break;
        }

        return $scheduled_time;
    }

    public function set_schedule($unit, $hour, $day = [])
    {
        $this->schedule['schedule'] = [
            'unit' => $unit,
            'hour' => $hour,
            'day' => $day
        ];
    }

    public function get_schedule($current_time)
    {
        if ($this->schedule['type'] != 'delay') {
            return $current_time;
        }

        $schedule_time = -1;
        $days = !empty($this->schedule['schedule']['day']) ? $this->schedule['schedule']['day'] : [0];
        $hours = $this->schedule['schedule']['hour'];
        if (empty($hours)) {
            return $current_time;
        }

        foreach ($days as $day) {
            foreach ($hours as $hour) {
                $row_time = $this->calculate_scheduled_time($this->schedule['schedule']['unit'], $day, $hour, $current_time);
                if ($schedule_time == -1 || $row_time < $schedule_time) {
                    $schedule_time = $row_time;
                }
            }
        }

        if ($schedule_time == -1) {
            return $current_time;
        }

        return $schedule_time;
    }

    public function get_delay()
    {
        if ($this->schedule['type'] != 'delay') {
            return 0;
        }

        $delay_settings = $this->schedule['delay'];
        $interval = isset($delay_settings['interval'], $delay_settings['unit']) ? intval($delay_settings['interval']) : 0;
        if ($interval <= 0) {
            return 0;
        }

        $unit = $delay_settings['unit'];
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

    public function set_delay($interval = 0, $unit = 'day')
    {
        $this->schedule['type'] = 'delay';


        $interval = intval($interval);

        switch ($unit) {
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
            case 'day':
            case 'days':
            default:
                $unit = 'day';
                break;
        }

        $this->schedule['delay'] = [
            'interval' => $interval,
            'unit' => $unit
        ];
    }

    public function data()
    {
        return [
            'id' => $this->get_id(),
            'name' => $this->get_name(),
            'schedule' => $this->schedule,
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
