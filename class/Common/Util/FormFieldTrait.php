<?php

namespace EmailWP\Common\Util;

trait FormFieldTrait
{
    protected $_fields;

    public function register_field($label, $id, $options = [])
    {
        $this->_fields[] = [
            'label' => $label,
            'id' => $id,
            'type' => isset($options['type']) ? $options['type'] : 'text',
            'options' => isset($options['options']) ? $options['options'] : [],
            'tooltip' => isset($options['tooltip']) ? $options['tooltip'] : '',
            'placeholder' => isset($options['placeholder']) ? $options['placeholder'] : 'Select option'
        ];
    }

    public function get_fields()
    {
        return $this->_fields;
    }
}
