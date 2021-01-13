<?php

namespace EmailWP\Common\Model;

class AutomationQueueModel
{
    private $_id;
    private $_automation_id;
    private $_status;
    private $_status_message;
    private $_date;
    private $_scheduled;
    private $_attempts;

    public function __construct($data = null)
    {
        $this->setup_data($data);
    }

    private function setup_data($data)
    {
        if (is_array($data)) {
            $this->_id = isset($data['id']) ? $data['id'] : null;
            $this->_automation_id = isset($data['automation_id']) ? $data['automation_id'] : null;
            $this->_status = isset($data['status']) ? $data['status'] : null;
            $this->_status_message = isset($data['status_message']) ? $data['status_message'] : null;
            $this->_date = isset($data['modified']) ? $data['modified'] : null;
            $this->_scheduled = isset($data['scheduled']) ? $data['scheduled'] : null;
            $this->_attempts = isset($data['attempts']) ? $data['attempts'] : 0;
        }
    }

    public function get_id()
    {
        return $this->_id;
    }

    public function get_status()
    {
        switch ($this->_status) {
            case 'Y':
                return 'complete';
            case 'S':
                return 'scheduled';
            case 'E':
                return 'error';
            case 'F':
                return 'failed';
            default:
                return $this->_status;
        }
    }

    public function get_message()
    {
        switch ($this->_status) {
            case 'Y':
                return $this->_status_message;
            case 'S':
                return 'Scheduled on ' . $this->_scheduled;
            case 'E':
                return 'Retry #' . $this->_attempts . ' on ' . date('Y-m-d H:i:s', strtotime('+5 minutes', strtotime($this->_date))) . ', Last Error: ' . $this->_status_message;
            case 'F':
                return 'Failed after ' . $this->_attempts . ' attempts, Last Error: ' . $this->_status_message;
            default:
                return $this->_status_message;
        }
    }

    public function get_date()
    {
        return $this->_date;
    }

    public function data()
    {
        return [
            'id' => $this->_id,
            'automation' => '',
            'status' => $this->get_status(),
            'message' => $this->get_message(),
            'date' => $this->get_date()
        ];
    }
}
