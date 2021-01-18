<?php

namespace EmailWP\Common\Placeholder;

class PlaceholderManager
{
    /**
     * @var EventHandler $event_handler
     */
    protected $event_handler;

    private $_placeholders;

    public function __construct($event_handler)
    {
        $this->event_handler = $event_handler;
    }

    public function get_placeholders()
    {

        if (!is_null($this->_placeholders)) {
            return $this->_placeholders;
        }

        $this->_placeholders = [];

        $placeholders = $this->event_handler->run('placeholders.register', [[]]);
        $placeholders = array_merge($placeholders, [
            UserPlaceholder::class,
            PostPlaceholder::class,
            GeneralPlaceholder::class
        ]);

        if (class_exists('WooCommerce')) {
            $placeholders = array_merge($placeholders, [
                WooCommerceOrderPlaceholder::class,
                WooCommerceAbandonedCartPlaceholder::class
            ]);
        }

        foreach ($placeholders as $class) {
            $placeholder = new $class;
            $this->_placeholders[$placeholder->get_id()] = $placeholder;
        }

        return $this->_placeholders;
    }

    public function get_placeholder($id)
    {
        $placeholders = $this->get_placeholders();

        if (!isset($placeholders[$id])) {
            return new \WP_Error('EWP_AM_1', 'Unable to locate event: ' . $id);
        }

        return $placeholders[$id];
    }

    public function has_placeholder($id)
    {
        $placeholders = $this->get_placeholders();
        return isset($placeholders[$id]);
    }

    public function replace_placeholders($input, $data)
    {
        $input = preg_replace_callback('/{{(.*?)}}/', function ($matches) use ($data) {

            if (strpos($matches[1], '|') !== false) {
                list($key, $arg_string) = explode('|', $matches[1]);
            } else {
                $key = $matches[1];
                $arg_string = '';
            }

            $key = strtolower(trim($key));

            $arg_string = trim($arg_string);
            $args = [];
            $found = preg_match_all('#([^\s=]+)\s*=\s*(\'[^<\']*\'|"[^<"]*")#', $arg_string, $arg_matches, PREG_SET_ORDER);
            if ($found > 0) {
                foreach ($arg_matches as $attr) {
                    $args[$attr[1]] = substr($attr[2], 1, -1);
                }
            }

            list($group, $var) = explode('.', $key);

            if ($this->has_placeholder($group)) {
                return $this->get_placeholder($group)->replace($var, $data, $args);
            }

            return $matches[0];
        }, $input);
        return $input;
    }

    public function reset()
    {
        foreach ($this->get_placeholders() as $placeholder) {
            $placeholder->reset();
        }
    }

    public function cancel()
    {
        foreach ($this->get_placeholders() as $placeholder) {
            $placeholder->cancel();
        }
    }
}
