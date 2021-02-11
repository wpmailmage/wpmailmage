<?php

namespace EmailWP\Common\Rest;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Action\SendEmailTemplate\DefaultSendEmailTemplate;
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
use EmailWP\Common\Util\Logger;

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

        // Load WC Cart when session if null
        // $rest_prefix = $this->properties->rest_namespace . '/' . $this->properties->rest_version . '/cart';
        // if (false !== strpos($_SERVER['REQUEST_URI'], $rest_prefix)) {
        //     add_action('wp_loaded', function () {

        //         if (did_action('woocommerce_init')) {
        //             return;
        //         }

        //         if (is_null(WC()->session)) {
        //             wc_load_cart();
        //         }
        //     }, PHP_INT_MAX);
        // }
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

        register_rest_route($namespace, '/queue/(?P<id>\d+)/run', array(
            array(
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => array($this, 'run_queue_item'),
                'permission_callback' => array($this, 'get_permission')
            ),
        ));

        register_rest_route($namespace, '/queue/(?P<id>\d+)/cancel', array(
            array(
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => array($this, 'cancel_queue_item'),
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

        register_rest_route($namespace, '/preview-data', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'get_preview_data'),
                'permission_callback' => array($this, 'get_permission')
            )
        ));
        register_rest_route($namespace, '/preview', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'preview'),
                'permission_callback' => array($this, 'get_permission')
            )
        ));

        // register_rest_route($namespace, '/cart', array(
        //     array(
        //         'methods'             => \WP_REST_Server::CREATABLE,
        //         'callback'            => array($this, 'save_cart'),
        //         'permission_callback' => '__return_true'
        //     )
        // ));


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
                'placeholders' => $event->get_placeholders(),
                'schedule' => $event->has_schedule() ? 'yes' : 'no',
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
        $page = intval($request->get_param('page'));
        $per_page = intval($request->get_param('per_page'));
        $output = [];

        $where = '';
        if (!is_null($id)) {
            $where = " AND automation_id = '" . $id . "'";
        }

        $per_page = $per_page > 0 ? $per_page : 10;
        $page = $page > 1 ? $page : 1;
        $limit = ' LIMIT ' . ($page - 1) * $per_page . ',' . $per_page;

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $output = [];
        $results = $wpdb->get_results("SELECT * FROM {$this->properties->table_automation_queue} WHERE 1=1 " . $where . " ORDER BY scheduled DESC, modified DESC" . $limit, ARRAY_A);
        foreach ($results as $row) {
            $automation_queue_model = new AutomationQueueModel($row);
            $output[] = $automation_queue_model->data();
        }

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->properties->table_automation_queue} WHERE 1=1 " . $where);

        $last_ran = get_option('ewp_last_ran');
        if ($last_ran) {
            $last_ran = current_time('timestamp') - $last_ran;
            $last_ran .= 's ago';
            // $last_ran = date('Y-m-d H:i:s', $last_ran);
        }

        return $this->http->end_rest_success(['data' => $output, 'updated' => $last_ran, 'total' => intval($total), 'page' => $page]);
    }

    public function get_logs(\WP_REST_Request $request)
    {
        $id = intval($request->get_param('id'));
        $page = intval($request->get_param('page'));
        $output = [];

        $where = '';
        if (!is_null($id)) {
            $where = " AND automation_id = '" . $id . "'";
        }

        $per_page = 10;
        $page = $page > 1 ? $page : 1;
        $limit = ' LIMIT ' . ($page - 1) * $per_page . ',' . $per_page;

        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $output = [];
        $results = $wpdb->get_results("SELECT * FROM {$this->properties->table_automation_queue} WHERE 1=1 AND (status = 'Y' OR status = 'F') " . $where . " ORDER BY modified DESC" . $limit, ARRAY_A);
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
        exit;
    }

    public function save_cart(\WP_REST_Request $request)
    {
        // TODO: Write order to abandoned cart table
        $data = $request->get_body_params();

        if (!WC()->session) {
            Logger::write(__METHOD__ . ' No WC Session');
            return $this->http->end_rest_error("No WC Session");
        }

        $session_id = WC()->session->get_customer_id();

        $automation_woocommerce_cart = new AutomationWoocommerceCart($session_id);
        $automation_woocommerce_cart->set_cart(WC()->cart->get_cart_for_session());
        $automation_woocommerce_cart->set_data($data);
        $result = $automation_woocommerce_cart->save();

        return $this->http->end_rest_success($result);
    }

    public function run_queue_item(\WP_REST_Request $request)
    {
        $queue_id = $request->get_param('id');
        $result = $this->automation_manager->run($queue_id);
        return $this->http->end_rest_success($result);
    }

    public function cancel_queue_item(\WP_REST_Request $request)
    {
        $queue_id = $request->get_param('id');
        /**
         * @var \WPDB $wpdb
         */
        global $wpdb;
        $result = $wpdb->update($this->properties->table_automation_queue, ['status' => 'F', 'status_message' => 'Manually cancelled', 'ran' => current_time('mysql')], ['id' => $queue_id]);
        return $this->http->end_rest_success($result > 0 ? true : false);
    }

    public function get_preview_data(\WP_REST_Request $request)
    {
        $placeholders = $request->get_param('placeholders');
        $result = [];

        foreach ($placeholders as $placeholder) {

            // TODO: Move this into placeholder manager
            $placeholder_class = $this->placeholder_manager->get_placeholder($placeholder);
            $result[$placeholder] = $placeholder_class->get_items();
        }

        return $this->http->end_rest_success($result);
    }

    /**
     * @var \WP_Error
     */
    private $wp_mail_error = false;

    public function preview(\WP_REST_Request $request)
    {
        $this->placeholder_manager->reset();

        $to = $request->get_param('email');

        if (!is_email($to)) {
            return $this->http->end_rest_error("Please enter a valid email address.");
        }

        $event_data = $request->get_param('event');
        $event_data = $this->event_manager->load_event_data($event_data);

        $settings = $request->get_param('settings');

        // load template
        $templates = apply_filters('ewp/send_email/register_template', [
            'default' => [
                'label' => 'Default',
                'class' => DefaultSendEmailTemplate::class
            ]
        ]);

        $template_id = $settings['template'];
        $template_id = !empty($template_id) && isset($templates[$template_id]) ? $template_id : 'default';
        $template = new $templates[$template_id]['class'];

        $subject = $this->placeholder_manager->replace_placeholders($settings['subject'], $event_data);
        $message = nl2br($settings['message']);
        $message = $this->placeholder_manager->replace_placeholders($message, $event_data);


        $template->set_subject($subject);
        $template->set_message($message);
        if (!isset($settings['show_unsubscribe']) || $settings['show_unsubscribe'] !== 'no') {
            $template->add_unsubscribe_url(add_query_arg(['ewp_unsubscribe' => urlencode(base64_encode('preview'))], site_url()));
        }
        $message = $template->render();

        $this->placeholder_manager->cancel();

        $headers = ["Content-Type: text/html"];

        add_action('wp_mail_failed', [$this, 'capture_wp_mail_error']);

        $result = wp_mail($to, 'Preview: ' . $subject, $message, $headers);
        if (!$result) {
            return $this->http->end_rest_error($this->wp_mail_error->get_error_message());
        }

        return $this->http->end_rest_success(sprintf('Preview email sent to %s.', $to));
    }

    public function capture_wp_mail_error($wp_error)
    {
        $this->wp_mail_error = $wp_error;
    }
}
