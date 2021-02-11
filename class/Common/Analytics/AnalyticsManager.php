<?php

namespace EmailWP\Common\Analytics;

use DateTime;
use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\Common\Properties\Properties;
use EmailWP\Common\UI\ViewManager;
use EmailWP\Container;

class AnalyticsManager
{
    private $_cookie_key = 'ewp_referral_session';

    /**
     * @var EventHandler $event_handler
     */
    protected $event_handler;

    protected $_trackers = [];

    public function __construct($event_handler)
    {
        $this->event_handler = $event_handler;

        if (!is_admin()) {
            add_filter('query_vars', [$this, 'register_query_vars']);
            add_action('wp', [$this, 'check_query_var_referral']);
        }
    }

    public function register()
    {
        $analytics = $this->event_handler->run('analytics.register', [[]]);

        if (class_exists('WooCommerce')) {
            $analytics = array_merge($analytics, [
                WooCommerceTracker::class
            ]);
        }

        if (!empty($analytics)) {
            foreach ($analytics as $class) {
                $this->_trackers[] = new $class($this->_cookie_key);
            }
        }
    }

    public function register_query_vars($query_vars)
    {
        $query_vars[] = 'ewp_ref_session';
        $query_vars[] = 'ewp_unsubscribe';
        return $query_vars;
    }

    public function check_query_var_referral()
    {
        $session = get_query_var('ewp_ref_session', '');
        $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : parse_url(get_option('siteurl'), PHP_URL_HOST);

        if ($session) {

            // only record activity if its not extending an existing cookie
            if (!isset($_COOKIE[$this->_cookie_key]) || $_COOKIE[$this->_cookie_key] !== strval($session)) {
                /**
                 * @var \WPDB $wpdb
                 */
                global $wpdb;

                $properties = Container::getInstance()->get('properties');
                $wpdb->insert($properties->table_automation_queue_activity, [
                    'queue_id' => intval($session),
                    'type' => 'click',
                    'created' => current_time('mysql')
                ]);
            }

            if (version_compare(PHP_VERSION, '7.3', '>=')) {
                setcookie($this->_cookie_key, strval($session), [
                    'expires' => time() + MONTH_IN_SECONDS,
                    'path' => '/',
                    'domain' => $domain,
                    'samesite' => 'Lax'
                ]);
            } else {
                setcookie($this->_cookie_key, strval($session), time() + MONTH_IN_SECONDS, '/', $domain);
            }
        }

        $unsubscribe_email = get_query_var('ewp_unsubscribe', '');
        if ($unsubscribe_email) {

            $unsubscribe_email = base64_decode(urldecode($unsubscribe_email));

            /**
             * @var \WPDB $wpdb
             */
            global $wpdb;

            /**
             * @var Properties $properties
             */
            $properties = Container::getInstance()->get('properties');

            /**
             * @var ViewManager $view_manager
             */
            $view_manager = Container::getInstance()->get('view_manager');

            // escape early if preview email link
            if ($unsubscribe_email === 'preview') {
                $view_manager->view('unsubscribe/success', compact(['view_manager']));
                exit;
            }

            $subscriber = $wpdb->get_row("SELECT * FROM {$properties->table_subscribers} WHERE LOWER(email)='" . strtolower($unsubscribe_email) . "' LIMIT 1", ARRAY_A);
            if ($subscriber['status'] == 'U') {
                $view_manager->view('unsubscribe/success', compact(['view_manager']));
                exit;
            }

            $result = false;
            $subscriber_id = null;

            if ($subscriber) {
                $subscriber_id = intval($subscriber['id']);

                if ($subscriber_id > 0) {
                    $result = $wpdb->update($properties->table_subscribers, [
                        'status' => 'U',
                        'modified' => current_time('mysql')
                    ], ['id' => $subscriber_id]);
                }
            } else {
                $created = current_time('mysql');
                $result = $wpdb->insert($properties->table_subscribers, [
                    'email' => $unsubscribe_email,
                    'created' => $created,
                    'modified' => $created,
                    'status' => 'U',
                    'source' => 'automation'
                ]);
            }

            if ($result) {
                $view_manager->view('unsubscribe/success', compact(['view_manager']));
                exit;
            }

            $view_manager->view('unsubscribe/error', compact(['view_manager']));
            exit;
        }
    }

    public function get_chart_data($id, $end_time, $length, $grouped = 'day', $date_unit = 'D', $keys = [])
    {
        if (empty($keys)) {
            return false;
        }

        /**
         * @var Properties $properties
         */
        $properties = Container::getInstance()->get('properties');

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        switch ($grouped) {
            case 'day':
                $date_format = '%Y-%m-%d';
                $offset_unit = DAY_IN_SECONDS;
                $mysql_date_format = 'Y-m-d';
                $time_unit = 'day';
                break;
            case 'week':
                // Week starts on monday
                $date_format = '%u';
                $offset_unit = WEEK_IN_SECONDS;
                $mysql_date_format = 'W';
                $time_unit = 'week';
                break;
            case 'month':
                $date_format = '%Y-%m';
                $offset_unit = MONTH_IN_SECONDS;
                $mysql_date_format = 'Y-m';
                $time_unit = 'month';
                break;
            default:
                return false;
        }

        if ($length < 0) {
            $start_time = strtotime($length . ' ' . $time_unit . 's', $end_time);
        } else {
            $start_time = false;
        }


        $id_query = '';
        if (intval($id) > 0) {
            $id_query = " INNER JOIN {$properties->table_automation_queue} as `q` ON  q.id = qa.queue_id AND q.automation_id='" . intval($id) . "' ";
        }

        $key_checker = [];
        foreach ($keys as $key) {
            $key_checker_parts = explode('|', $key);
            foreach ($key_checker_parts as $part) {
                if (!isset($key_checker[$part])) {
                    $key_checker[$part] = $key;
                }
            }
        }

        $query = "SELECT DATE_FORMAT(qa.created, '" . $date_format . "') as `date`, qa.type, COUNT(qa.type) as `count`
        FROM `" . $properties->table_automation_queue_activity . "` as `qa`" . $id_query .
            "WHERE qa.created <= '" . date('Y-m-d 23:59:59', $end_time) . "' " . ($start_time ? " AND qa.created > '" . date('Y-m-d 23:59:59', $start_time) . "' " : "") . " AND qa.type IN ('" . implode("', '", array_keys($key_checker)) . "') GROUP BY `date`, type ORDER BY `date` ASC";

        $rows = $wpdb->get_results($query, ARRAY_A);
        $tmp_data = [];

        $output = [];
        $totals = [];
        $row_template = [];
        foreach ($keys as $key) {
            $row_template[$key] = 0;
            $output[$key] = [];
            $totals[$key] = 0;
        }

        if ($length == 0) {
            $oldest_time = array_reduce($rows, function ($carry, $item) {
                $created = strtotime($item['date']);
                return $created < $carry ? $created : $carry;
            }, $end_time);

            $d1 = new DateTime();
            $d1->setTimestamp($end_time);

            $d2 = new DateTime();
            $d2->setTimestamp($oldest_time);

            switch ($grouped) {
                case 'day':
                    $length = $d1->diff($d2)->d;
                    break;
                case 'week':
                    $length = ceil($d1->diff($d2)->d / 7);
                    break;
                case 'month':
                    $length = $d1->diff($d2)->m;
                    break;
            }
        }


        foreach ($rows as $row) {
            if (!isset($tmp_data[$row['date']])) {

                $tmp_data[$row['date']] = $row_template;
            }

            $tmp_data[$row['date']][$row['type']] += $row['count'];
        }

        $emails = [];
        $clicks = [];
        $read = [];
        $ticks = null;
        if (absint($length) > 7) {

            $tmp_end_time = $end_time;
            if ($time_unit === 'month') {
                $tmp_end_time = strtotime(date('Y-m-01', $end_time));
            }

            if (absint($length) % 2 == 0) {
                // length + 1 is odd
                $ticks = [
                    date($date_unit, $tmp_end_time - $offset_unit * 0),
                    date($date_unit, $tmp_end_time - $offset_unit * floor(absint($length) / 2)),
                    date($date_unit, $tmp_end_time - $offset_unit * absint($length)),
                ];
            } else {
                // length + 1 is even
                $ticks = [
                    date($date_unit, $tmp_end_time - $offset_unit * 0),
                    date($date_unit, $tmp_end_time - $offset_unit * floor(absint($length) * 0.25)),
                    date($date_unit, $tmp_end_time - $offset_unit * ceil(absint($length) * 0.5)),
                    date($date_unit, $tmp_end_time - $offset_unit * ceil(absint($length) * 0.75)),
                    date($date_unit, $tmp_end_time - $offset_unit * absint($length)),
                ];
            }
        }

        for ($i = 0; $i <= absint($length); $i++) {

            $day_template = [];
            foreach ($keys as $key) {
                $day_template[$key] = 0;
            }

            // if 31st and monus 1 month it breaks
            if ($time_unit === 'month') {
                $time = strtotime('-' . $i . ' ' . $time_unit . 's',  strtotime(date('Y-m', $end_time)));
            } else {
                $time = strtotime('-' . $i . ' ' . $time_unit . 's',  $end_time);
            }

            $today = date($mysql_date_format, $time);

            $day_data = array_filter($rows, function ($item) use ($today) {
                return $item['date'] === $today;
            });

            if (!empty($day_data)) {
                foreach ($day_data as $row) {

                    if (isset($day_template[$key_checker[$row['type']]])) {
                        $day_template[$key_checker[$row['type']]] += $row['count'];
                    }
                }
            }

            foreach (array_keys($output) as $k) {
                $output[$k][] = ['x' => date($date_unit, $time), 'y' => $day_template[$k]];
                $totals[$k] += $day_template[$k];
            }

            // $emails[] = ['x' => date($date_unit, $time), 'y' => $day_emails];
            // $clicks[] = ['x' => date($date_unit, $time), 'y' => $day_clicks];
            // $read[] = ['x' => date($date_unit, $time), 'y' => $day_read];
        }

        return [$output, $ticks, array_values($totals)];
    }


    public function get_charts($id = null)
    {
        // Timeframes: -7d, -30d, -3m, -1y
        $data = [];

        /**
         * @var AutomationManager $automation_manager
         */
        $automation_manager = Container::getInstance()->get('automation_manager');

        /**
         * @var PlaceholderManager $placeholder_manager
         */
        $placeholder_manager = Container::getInstance()->get('placeholder_manager');

        $automation_model = $automation_manager->get_automation_model($id);
        $event = $automation_model->get_event();
        $action = $automation_model->get_action();
        $trigger_event = 'email';

        if ($action == 'send_email' || $action == 'email') {
            switch ($event) {
                case 'woocommerce.abandoned_cart':
                    list($chart_data, $ticks) = $this->get_chart_data($id, time(), 0, 'day', 'jS M', [$trigger_event, 'recovered::wc_cart']);
                    $data[] = [
                        'id' => 'abandoned-cart',
                        'title' => 'Abandoned Carts',
                        'legends' => ['Abandoned', 'Recovered'],
                        'ticks' => $ticks,
                        'data' => $chart_data
                    ];
                    break;
                case 'woocommerce.order_status':
                    // TODO: Check if the action contains review items 
                    list($chart_data, $ticks, $totals) = $this->get_chart_data($id, time(), 0, 'day', 'jS M', [$trigger_event, 'read', 'click', 'wc_review']);

                    $legends = ['Emails sent - ' . $totals[0], 'Emails read - ' . $totals[1]];
                    $title = 'Reports';
                    if ($totals[2] > 0) {
                        $legends[] = 'Emails clicked - ' . $totals[2];
                    }
                    if ($totals[3] > 0) {
                        $legends[] = 'Products Reviewed - ' . $totals[3];
                        $title = 'Products Reviewed';
                    }

                    $data[] = [
                        'id' => 'reviews',
                        'title' => $title,
                        'legends' => $legends,
                        'ticks' => $ticks,
                        'data' => $chart_data
                    ];

                    // if the action contains coupon generation
                    list($chart_data, $ticks, $totals) = $this->get_chart_data($id, time(), 0, 'day', 'jS M', [$trigger_event, 'read', 'generate::wc_coupon', 'used::wc_coupon']);
                    if ($totals[2] > 0) {
                        $data[] = [
                            'id' => 'coupons',
                            'title' => 'Coupons Used',
                            'legends' => ['Emails sent - ' . $totals[0], 'Emails read - ' . $totals[1], 'Coupons created - ' . $totals[2], 'Coupons used - ' . $totals[3]],
                            'ticks' => $ticks,
                            'data' => $chart_data
                        ];
                    }
                    break;
                default:
                    list($chart_data, $ticks, $totals) = $this->get_chart_data($id, time(), 0, 'day', 'jS M', [$trigger_event, 'read']);
                    $data[] = [
                        'id' => 'General',
                        'title' => 'All time',
                        'legends' => ['Emails sent - ' . $totals[0], 'Emails read - ' . $totals[1]],
                        'ticks' => $ticks,
                        'data' => $chart_data
                    ];
                    break;
            }
        } elseif ($action == 'log') {
            list($chart_data, $ticks, $totals) = $this->get_chart_data($id, time(), 0, 'day', 'jS M', ['log']);
            $data[] = [
                'id' => 'Events Logged',
                'title' => 'All time',
                'legends' => ['Event Logs - ' . $totals[0]],
                'ticks' => $ticks,
                'data' => $chart_data
            ];
        }

        foreach ($this->_trackers as $tracker) {
            $data = array_merge($data, $tracker->get_charts());
        }

        return $data;
    }
}
