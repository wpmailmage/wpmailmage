<?php

namespace EmailWP\Common\Event;

use EmailWP\Common\EventInterface;

class PostStatusChangeEvent extends AbstractEvent implements EventInterface
{
    /**
     * Post Status string: public, future, draft, pending, private, trash
     * @var string $_status
     */
    private $_status;

    private $_post_type;

    private $_settings = [];

    public function __construct($args = [])
    {
        $this->_settings = $args;

        // $this->_status = isset($args['post_status']) ? $args['post_status'] : null;
        // $this->_post_type = isset($args['post_type']) ? $args['post_type'] : null;

        $this->register_fields();
    }

    public function register_fields()
    {
        $post_type_options = [
            ['value' => '*', 'label' => 'Any']
        ];
        foreach (get_post_types([], 'objects') as $post_type) {
            $post_type_options[] = ['value' => $post_type->name, 'label' => $post_type->label];
        }

        $this->register_field('Post Type', 'post_type', [
            'type' => 'select',
            'options' => $post_type_options
        ]);

        $status_options = [
            ['value' => '*', 'label' => 'Any']
        ];
        foreach (get_post_stati() as $id => $label) {
            $status_options[] = ['value' => $id, 'label' => $label];
        }

        $this->register_field('Post Status', 'post_status', [
            'type' => 'select',
            'options' => $status_options
        ]);
    }

    public function get_label()
    {
        return 'On Post Status Changed';
    }

    public function install_listener()
    {
        add_action('transition_post_status', [$this, 'on_transition_post_status'], 10, 3);
    }

    private function verify_settings($new_post_type, $new_status)
    {
        $matched = false;
        foreach ($this->_settings as $settings) {
            $post_type = $settings['post_type'];
            if ($post_type !== '*' && $post_type !== $new_post_type) {
                continue;
            }

            $post_status = $settings['post_status'];
            if ($post_status !== '*' && $post_status !== $new_status) {
                continue;
            }

            $matched = true;
            break;
        }
        return $matched;
    }

    public function on_transition_post_status($new_status, $old_status, $post)
    {
        if ($new_status == $old_status) {
            return;
        }

        $post_type = get_post_type($post);
        if ($this->verify_settings($post_type, $new_status)) {
            $this->triggered(['post' => $post->ID]);
        }
    }

    public function get_placeholders()
    {
        return ['post'];
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
        $post_type = get_post_type($event_data['post']);
        $post_status = get_post_status($event_data['post']);
        if ($this->verify_settings($post_type, $post_status)) {
            return true;
        }

        $this->set_log_message("Post status change is no longer valid.");
        return false;
    }
}
