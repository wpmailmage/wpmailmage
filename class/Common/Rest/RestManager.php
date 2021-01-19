<?php

namespace EmailWP\Common\Rest;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Analytics\AnalyticsManager;
use EmailWP\Common\Automation\AutomationManager;
use EmailWP\Common\Event\EventManager;
use EmailWP\Common\EventInterface;
use EmailWP\Common\Http\Http;
use EmailWP\Common\Model\AutomationModel;
use EmailWP\Common\Model\AutomationQueueModel;
use EmailWP\Common\Model\AutomationWoocommerceCart;
use EmailWP\Common\Placeholder\PlaceholderManager;
use EmailWP\Common\Properties\Properties;

class RestManager
{
    /**
     * @var ActionManager
     */
    protected $action_manager;

    /**
     * @var AnalyticsManager
     */
    protected $analytics_manager;

    /**
     * @var AutomationManager
     */
    protected $automation_manager;

    /**
     * @var EventManager
     */
    protected $event_manager;

    /**
     * @var Http
     */
    protected $http;

    /**
     * @var PlaceholderManager
     */
    protected $placeholder_manager;

    /**
     * @var Properties
     */
    protected $properties;

    public function __construct($http, $event_manager, $action_manager, $automation_manager, $properties, $analytics_manager, $placeholder_manager)
    {
        $this->http = $http;
        $this->event_manager = $event_manager;
        $this->action_manager = $action_manager;
        $this->automation_manager = $automation_manager;
        $this->properties = $properties;
        $this->analytics_manager = $analytics_manager;
        $this->placeholder_manager = $placeholder_manager;
    }

    public function register()
    {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    public function register_routes()
    {
        $namespace = $this->properties->rest_namespace . '/' . $this->properties->rest_version;

        register_rest_route($namespace, '/events', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_events'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/actions', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_actions'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/charts', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_charts'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));
        register_rest_route($namespace, '/charts/(?P<id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_charts'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/automation', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'save_automation'),
                'permission_callback' => array($this, 'get_permission')
            )
        ));

        register_rest_route($namespace, '/automation/(?P<id>\d+)', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_automation'),
                'permission_callback' => array($this, 'get_permission')
            ),
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'save_automation'),
                'permission_callback' => array($this, 'get_permission')
            ),
            array(
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'delete_automation'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/automation/(?P<id>\d+)/queue', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_queue'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/automation/(?P<id>\d+)/logs', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_logs'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/automations', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'get_automations'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        // Email open
        register_rest_route($namespace, '/automations/queue/(?P<queue_id>\S+)\.png', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array($this, 'track_read'),
                'permission_callback' => '__return_true'
            ),
        ));

        register_rest_route($namespace, '/cart', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'save_cart'),
                'permission_callback' => '__return_true'
            )
        ));
    }

    public function get_permission()
    {

        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', esc_html__('You do not have permissions.', $this->properties->plugin_domain), array('status' => 401));
        }

        return true;
    }

    public function get_events(\WP_REST_Request $request)
    {
        $result = [];

        $events = $this->event_manager->get_events();
        foreach ($events as $event_id => $event_class) {

            /**
             * @var EventInterface $event
             */
            $event = new $event_class();

            $result[] = [
                'id' => $event_id,
                'label' => $event->get_label(),
                'fields' => $event->get_fields(),
                'placeholders' => $event->get_placeholders()
            ];
        }

        return $this->http->end_rest_success($result);
    }

    public function get_actions(\WP_REST_Request $request)
    {
        $result = [];

        $actions = $this->action_manager->get_actions();
        foreach ($actions as $action_id => $action_class) {

            $action = new $action_class();

            $result[] = [
                'id' => $action_id,
                'label' => $action->get_label(),
                'fields' => $action->get_fields()
            ];
        }

        $placeholders = [];
        $all_placeholders = $this->placeholder_manager->get_placeholders();
        if (!empty($all_placeholders)) {
            foreach ($all_placeholders as $placeholder) {

                $vars = array_keys($placeholder->get_variables());
                $id = $placeholder->get_id();
                $placeholders = array_reduce($vars, function ($carry, $item) use ($id) {

                    if (!isset($carry[$id])) {
                        $carry[$id] = [];
                    }

                    $carry[$id][] = '{{' . $id . '.' . $item . '}}';
                    return $carry;
                }, $placeholders);
            }
        }

        return $this->http->end_rest_success(['actions' => $result, 'placeholders' => $placeholders]);
    }

    public function get_automations(\WP_REST_Request $request)
    {
        $output = [];
        $automations = $this->automation_manager->get_automations();
        foreach ($automations as $automation_model) {
            $output[] = $automation_model->data();
        }
        return $this->http->end_rest_success($output);
    }

    public function get_automation(\WP_REST_Request $request)
    {
        $id = intval($request->get_param('id'));
        $automation_model = $this->automation_manager->get_automation_model($id);
        if (!$automation_model) {
            return $this->http->end_rest_error("Invalid automation");
        }
        return $this->http->end_rest_success($automation_model->data());
    }

    public function save_automation(\WP_REST_Request $request)
    {
        $post_data = $request->get_json_params();

        $automation_model = new AutomationModel($post_data);
        $result = $automation_model->save();

        if (is_wp_error($result)) {
            return $this->http->end_rest_error($result->get_error_message());
        }
        return $this->http->end_rest_success($automation_model->data());
    }

    public function delete_automation(\WP_REST_Request $request)
    {
        $id = intval($request->get_param('id'));
        $result = $this->automation_manager->delete($id);
        if (!$result) {
            return $this->http->end_rest_error("unable to delete automation #" . $id);
        }

        return $this->get_automations($request);
    }

    public function get_queue(\WP_REST_Request $request)
    {
        $id = intval($request->get_param('id'));
        $output = [];

        $where = '';
        if (!is_null($id)) {
            $where = " AND automation_id = '" . $id . "'";
        }

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $output = [];
        $results = $wpdb->get_results("SELECT * FROM {$this->properties->table_automation_queue} WHERE 1=1 " . $where . " ORDER BY modified DESC", ARRAY_A);
        foreach ($results as $row) {
            $automation_queue_model = new AutomationQueueModel($row);
            $output[] = $automation_queue_model->data();
        }

        $last_ran = get_option('ewp_last_ran');
        if ($last_ran) {
            $last_ran = current_time('timestamp') - $last_ran;
            $last_ran .= 's ago';
            // $last_ran = date('Y-m-d H:i:s', $last_ran);
        }

        return $this->http->end_rest_success(['data' => $output, 'updated' => $last_ran]);
    }

    public function get_logs(\WP_REST_Request $request)
    {
        $id = intval($request->get_param('id'));
        $output = [];

        $where = '';
        if (!is_null($id)) {
            $where = " AND automation_id = '" . $id . "'";
        }

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $output = [];
        $results = $wpdb->get_results("SELECT * FROM {$this->properties->table_automation_queue} WHERE 1=1 AND (status = 'Y' OR status = 'F') " . $where . " ORDER BY modified DESC", ARRAY_A);
        foreach ($results as $row) {
            $automation_queue_model = new AutomationQueueModel($row);
            $output[] = $automation_queue_model->data();
        }

        return $this->http->end_rest_success($output);
    }

    public function get_charts(\WP_REST_Request $request)
    {
        $id = $request->get_param('id');
        $output = $this->analytics_manager->get_charts($id);
        return $this->http->end_rest_success($output);
    }

    public function track_read(\WP_REST_Request $request)
    {
        $queue_id = $request->get_param('queue_id');

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;

        $result = $wpdb->get_row("SELECT type FROM " . $this->properties->table_automation_queue . " as q LEFT JOIN " . $this->properties->table_automation_queue_activity . " as a ON q.id = a.queue_id AND a.type='read' WHERE q.id='" . $queue_id . "'", ARRAY_A);
        if ($result && is_null($result['type'])) {
            $wpdb->insert($this->properties->table_automation_queue_activity, ['type' => 'read', 'queue_id' => $queue_id, 'created' => current_time('mysql')]);
        }

        header('Content-Type: image/png');
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");

        $img = imagecreatetruecolor(1, 1);
        imagesavealpha($img, true);
        $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $color);
        imagepng($img);
    }

    public function save_cart(\WP_REST_Request $request)
    {
        // TODO: Write order to abandoned cart table
        $data = $request->get_body_params();
        $session_id = WC()->session->get_customer_id();

        $automation_woocommerce_cart = new AutomationWoocommerceCart($session_id);
        $automation_woocommerce_cart->set_cart(WC()->cart->get_cart_for_session());
        $automation_woocommerce_cart->set_data($data);
        $result = $automation_woocommerce_cart->save();

        return $this->http->end_rest_success($result);
    }
}
