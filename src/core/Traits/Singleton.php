<?php

namespace Ravand\Core\Traits;

trait Singleton
{
    private static $instance;

    protected function __construct()
    {}

    public static function getInstance()
    {
        return self::isInitialized() ? self::$instance : self::init();
    }

    public static function init()
    {
        return self::isInitialized() ? self::$instance : new static;
    }

    public static function isInitialized()
    {
        return isset(self::$instance);
    }

    public static function __callStatic($name, $args)
    {
        # Check For Methods
        return (self::$instance)->{$name}(...$args);
    }
}
