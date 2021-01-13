<?php

namespace EmailWP;

class Container
{
    public $providers = [];
    public $interceptors = [];
    public $classes = [];
    private $event_handler;

    private static $instance;

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }

    protected function __wakeup()
    {
    }

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function setupServiceProviders($is_pro = false)
    {
        $this->event_handler = new EventHandler();
        $potential_classes = [];

        if (true === $is_pro) {
            $potential_classes = ['EmailWP\Pro\ServiceProvider'];
        } else {
            $potential_classes = ['EmailWP\Free\ServiceProvider'];
        }

        foreach ($potential_classes as $class) {
            $this->maybeAddProvider($class);
        }

        if (!empty($this->providers)) {
            foreach ($this->providers as $provider) {
                $vars = get_object_vars($provider);
                foreach ($vars as $prop => $var) {
                    if (!isset($this->classes[$prop])) {
                        $this->classes[$prop] = $var;
                    }
                }
            }
        }
    }

    public function maybeAddProvider($class)
    {
        if (class_exists($class)) {
            $this->providers[$class] = new $class($this->event_handler);
        }
    }

    public function maybeAddInterceptor($class)
    {
        if (class_exists($class) && !isset($this->interceptors[$class])) {
            $this->interceptors[$class] = new $class();
        }
    }

    public function get($id)
    {
        if (empty($this->classes)) {
            $this->setupServiceProviders();
        }

        if (array_key_exists($id, $this->classes)) {
            return $this->classes[$id];
        }

        return false;
    }

    protected function _registerProvider($provider)
    {
    }
}
