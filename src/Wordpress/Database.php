<?php

namespace Pluguin\Wordpress;

use \Illuminate\Contracts\Container\Container;
use \Illuminate\Database\Capsule\Manager as Capsule;
use \Illuminate\Support\Fluent;

class Database extends Capsule
{
    /**
     * The database manager instance.
     *
     * @var \Illuminate\Database\DatabaseManager
     */
    protected $manager;

     /**
     * The current globally used instance.
     *
     * @var object
     */
    protected static $instance;

    /**
     * The container instance.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    protected $tablePrefix = '';

    /**
     * Create a new database capsule manager.
     *
     * @param  \Illuminate\Container\Container|null  $container
     * @return void
     */
    public function __construct()
    {
        $this->setupContainer();

        $this->setupDefaultConfiguration();

        $this->setupManager();

        $this->bootEloquent();

        $this->setAsGlobal();
    }

    /**
     * Setup the IoC container instance.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function setupContainer()
    {
        $this->container = new Container;

        $this->container->instance('config', new Fluent);
    }


    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::connection()->$method(...$parameters);
    }
}
