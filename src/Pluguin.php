<?php

namespace Pluguin;

use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Fluent;
use Pluguin\Contracts\Plugin;
use Pluguin\Database\MigrationServiceProvider;
use Pluguin\Events\Dispatcher;

final class Pluguin
{
    public $container;

    // private $bootstrappers = [
    //     \Pluguin\Foundation\Bootstrap\LoadConfiguration::class,
    //     \Pluguin\Foundation\Bootstrap\RegisterProviders::class,
    //     \Pluguin\Foundation\Bootstrap\BootProviders::class,
    // ];

    private static $instance;

    private function __construct()
    {
        $this->checkOptions();

        $this->setupContainer();

        $this->setupConfiguration();

        $this->registerServices();

        $this->setupEloquent();

        $this->registerAction();
    }

    private function checkOptions()
    {
        $options = \get_option("pluguin");

        if($options === false)
        {
            $options = [];
            $this->addOptions($options);
        }

        $this->options = $options;
    }

    private function addOptions(array $options)
    {
        \add_option("pluguin",$options,'','yes');
    }

    private function updateOptions(array $options)
    {
        \update_option("pluguin",$options);
    }

    private function setupContainer()
    {
        $this->container = new Container();
    }

    public function getContainer()
    {
        return $this->container;
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
            ],
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

        $this->registerFilesystemService();

        $this->registerDatabaseServices();
    }

    private function registerEventsService()
    {
        $this->container->singleton('events', function ($container) {
            return new Dispatcher($container);
        });
    }

    private function registerFilesystemService()
    {
        $this->container->singleton('files', function () {
            return new Filesystem;
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
            \do_action("pluguin", $this);
        });
    }

    public static function init()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
    }

    public function register(Plugin $plugin)
    {
        $plugin->instance('events', $this->container["events"]);
        $plugin->instance('db', $this->container["db"]);
        $plugin->instance('files', $this->container["files
        "]);

        $plugin->addDeferredServices([
            MigrationServiceProvider::class,
        ]);

        if(!isset($this->options["plugins"][$plugin::class]))
        {
            //never detected before, so run plugins installation hook

            $this->options["plugins"][$plugin::class] = [];
        }

        
        // $plugin->bootstrapWith($plugin->getBootstrappers());

        $pluginFile = $plugin->filePath();

        \register_activation_hook($pluginFile, $plugin::class."::activationHook");

        \register_deactivation_hook($pluginFile, $plugin::class."::activationHook");

        \register_activation_hook($pluginFile, );

        $plugin->init();
    }

    public static function getInstance()
    {
        static::init();

        return static::$instance;
    }
}
