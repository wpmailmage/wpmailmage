<?php

namespace EmailWP\Common\Automation;

use EmailWP\Common\Action\ActionManager;
use EmailWP\Common\Model\AutomationModel;

class MockAutomationManager extends AutomationManager
{
    /**
     * @var AutomationModel[]
     */
    private $_mock_automations;

    /**
     * @param EventHandler $event_handler
     * @param EventManager $event_manager
     * @param ActionManager $action_manager
     */
    public function __construct($event_handler, $event_manager, $action_manager)
    {
        parent::__construct($event_handler, $event_manager, $action_manager);

        $mock_1 = new AutomationModel(['id' => 1]);
        $mock_1->set_delay(5, 'minutes');
        $mock_1->set_name('Send Order Review Email after 1 week');
        $mock_1->set_event('woocommerce.order_status', [
            ['order_status' => 'wc-processing'],
            ['order_status' => 'wc-completed']
        ]);
        $mock_1->set_action('woocommerce.send_email', [
            'to' => '{{wc_order.email}}',
            'subject' => 'Thank you for your purchase #{{wc_order.id}}! Let us know what you think',
            'message' => 'Thank you for your purchase order #{{wc_order.id}}!

        Would you like to let us know what you think of our products? Please write a review for them:

        {{wc_order.items_review}}

        We hope to see you again here!'
        ]);

        $mock_2 = new AutomationModel(['id' => 2]);
        $mock_2->set_name('Send Post Email');
        $mock_2->set_event('post.order_status', [
            [
                'post_status' => 'publish',
                'post_type' => 'post',
            ],
            [
                'post_status' => 'pending',
                'post_type' => 'post',
            ],
            [
                'post_status' => 'draft',
                'post_type' => 'post',
            ],

        ]);
        $mock_2->set_action('send_email', [
            'to' => 'james@jclabs.co.uk',
            'subject' => 'Post #{{post.id}}',
            'message' => '#{{post.id}} - {{post.title}} has been updated by {{user.email}}',
        ]);

        $this->_mock_automations = [
            $mock_1, $mock_2
        ];
    }

    /**
     * Get list of active automations
     * 
     * @return AutomationModel[]
     */
    public function get_automations()
    {
        return $this->_mock_automations;
    }

    /**
     * Get automation
     * 
     * @return AutomationModel
     */
    public function get_automation_model($id)
    {
        if ($id instanceof AutomationModel) {
            return $id;
        }

        return $this->_mock_automations[intval($id) - 1];
    }
}
