<?php

namespace EmailWP\Free\Automation;

class AutomationManager extends \EmailWP\Common\Automation\AutomationManager
{
    /**
     * Get list of active automations
     * 
     * @return AutomationModel[]
     */
    final public function get_automations()
    {
        $result = array();
        $query  = new \WP_Query(array(
            'post_type'      => EWP_POST_TYPE,
            'posts_per_page' => 1,
            'orderby' => 'ID',
            'order' => 'ASC'
        ));

        foreach ($query->posts as $post) {
            $result[] = $this->get_automation_model($post);
        }
        return $result;
    }
}
