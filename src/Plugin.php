<?php

namespace Phoetik\Pluguin;

use Illuminate\Container\Container;
use RuntimeException;

class Plugin
{
    private static $instance;
    private $container;

    private function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(Container $container)
    {
        return $this->container;
    }


    public static function init()
    {
        $instance = static::$instance = new static(new Container);
    }

    public function __call($method, $args)
    {
        if(method_exists($this->container,$method))
        {
            return $this->container->$method(...$args);
        }

        return $this->$method(...$args);
    }

    public static function __callStatic($method, $args)
    {
        if (!isset(static::$instance)) {
            throw new RuntimeException('Instance is not initialized');
        }

        return static::$instance->$method(...$args);
    }
}
