<?php

namespace Pluguin;

use Pluguin\Events\Dispatcher;
use Pluguin\Contracts\Plugin;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Container\Container;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Fluent;

final class Pluguin
{
    private static $instance;

    public $container;

    private function __construct()
    {
        $this->setupContainer();

        $this->setupConfiguration();

        $this->registerServices();

        $this->setupEloquent();

        $this->registerAction();
    }

    private function setupContainer()
    {
        $this->container = new Container();
    }

    private function setupConfiguration()
    {
        $this->container->instance('config', new Fluent);

        $this->setupDatabaseConfiguration();
    }

    private function setupDatabaseConfiguration()
    {
        $this->container['config']['database.fetch'] = PDO::FETCH_OBJ;

        $this->container['config']['database.default'] = 'default';

        $this->container['config']['database.connections'] = [
            'default' => [
                'driver' => 'mysql',
                'host' => DB_HOST,
                'database' => DB_NAME,
                'username' => DB_USER,
                'password' => DB_PASSWORD,
                'charset' => 'utf8',
                'collation' => empty(DB_COLLATE) ? 'utf8_unicode_ci' : DB_COLLATE,
                'prefix' => $this->getWordpressTablePrefix(),
            ]
        ];
    }

    private function getWordpressTablePrefix()
    {
        global $wpdb;
        return $wpdb->prefix;
    }

    private function registerServices()
    {
        $this->container->instance('pluguin', $this);

        $this->container->instance(Container::class, $this->container);

        $this->registerEventsService();

        $this->registerDatabaseServices();
    }

    private function registerEventsService()
    {
        $this->container->singleton('events', function ($container) {
            return new Dispatcher($container);
        });
    }

    private function registerDatabaseServices()
    {
        $this->container->singleton('db.factory', function ($container) {
            return new ConnectionFactory($container);
        });

        $this->container->singleton('db', function ($container) {
            return new DatabaseManager($container, $container['db.factory']);
        });

        $this->container->bind('db.connection', function ($container) {
            return $container['db']->connection();
        });

        $this->container->bind('db.schema', function ($container) {
            return $container['db.connection']->getSchemaBuilder();
        });
    }

    private function setupEloquent()
    {
        Model::setConnectionResolver($this->container["db"]);
        Model::setEventDispatcher($this->container["events"]);
    }

    private function registerAction()
    {
        \add_action("plugins_loaded", function () {
            \do_action("pluguin");
        });
    }

    public static function init()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
    }

    public static function inject(Plugin $plugin)
    {
        static::init();

        $container = static::$instance->container;

        $plugin->instance('events', $container["events"]);
        $plugin->instance('db', $container["db"]);
    }

    
}
