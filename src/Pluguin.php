<?php

namespace Pluguin;

use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Fluent;
use Pluguin\Contracts\Foundation\Plugin;
use Pluguin\Database\MigrationServiceProvider;
use Pluguin\Events\Dispatcher;
use \BadMethodCallException;
use \PDO;

final class Pluguin
{
    public $container;

    public $options;

    private $plugins = [];

    private static $instance;

    private function __construct()
    {
        $this->checkOptions();

        $this->setupContainer();

        $this->setupConfiguration();

        $this->registerServices();

        $this->setupEloquent();

        $this->registerHooks();

        $this->registerAction();
    }

    private function checkOptions()
    {
        $this->options = \get_option("pluguin");

        if ($this->options === false) {
            $this->options = [
                "plugins" => []
            ];

            $this->addOptions();
        }
    }

    private function addOptions()
    {
        \add_option("pluguin", $this->options, '', 'yes');
    }

    private function updateOptions()
    {
        \update_option("pluguin", $this->options);
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

    private function registerHooks()
    {
        $pluguin = dirname(__DIR__)."/pluguin.php";

        

        \register_activation_hook($pluguin, self::class."::activationHook");

        \register_deactivation_hook($pluguin, self::class."::deactivationHook");

        \register_uninstall_hook($pluguin, self::class."::uninstallHook");
    }

    private function registerAction()
    {
        \add_action("plugins_loaded", function () {
            \do_action("pluguin", $this);
        });
    }

    public static function register(Plugin $plugin, $bootstrappers = null)
    {
        $pluguin = self::getInstance();

        $plugin->instance('events', $pluguin->container["events"]);
        $plugin->instance('db', $pluguin->container["db"]);
        $plugin->instance('files', $pluguin->container["files"]);

        $plugin->addDeferredServices([
            MigrationServiceProvider::class,
        ]);

        $pluginFile = $plugin->filePath();

        $plugin->bootstrapWith($bootstrappers ?? $plugin->getBootstrappers());

        $version = $plugin->version();

        if (!isset($pluguin->options["plugins"][$plugin::class])) {
            //never detected before, so run plugins installation hook
            $basename = \plugin_basename($pluginFile);

            $pluguin->options["plugins"][$basename] = [
                "version" => $version
            ];

            $plugin::installHook();
        }

        $versionOption = $pluguin->options["plugins"][$basename]["version"];

        if ($version > $versionOption) {
            $plugin->upgrade($versionOption, $version);
        } elseif ($version < $versionOption) {
            $plugin->downgrade($version, $versionOption);
        }

        $pluguin->updateOptions();

        $pluguin->plugins[$basename] = $plugin;

        \register_activation_hook($pluginFile, self::class."::activate_$basename");

        \register_deactivation_hook($pluginFile, self::class."::deactivate_$basename");

        \register_uninstall_hook($pluginFile, self::class."::uninstall_$basename");

        $plugin->init();
    }

    public static function init()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
    }

    public static function getInstance()
    {
        self::init();

        return self::$instance;
    }

    public static function activationHook()
    {
        $pluguin = self::getInstance();
    }

    public static function deactivationHook()
    {
        $pluguin = self::getInstance();

        if (!empty($pluguin->options["plugins"])) {
            $pluginNames = [];

            foreach ($pluguin->options["plugins"] as $pluginBasename => $data) {
                $pluginFile = WP_PLUGIN_DIR . "/" . $pluginBasename;

                $pluginInfo = \get_plugin_data($pluginFile);

                $pluginNames[] = $pluginInfo["Name"] ?? "";
            }

            $pluginNames = implode(", ", $pluginNames);

            $adminPluginsUrl = \admin_url('plugins.php');
            $error = "Deactivation failed because following plugin(s) depend(s) on Pluguin: <strong>$pluginNames</strong>.<br><br><a href='$adminPluginsUrl'>Return back to plugins.</a>";

            \wp_die($error);
        }
    }

    public function activate($plugin)
    {
        $plugin->activate();
    }

    public function deactivate($plugin)
    {
        $plugin->deactivate();
    }

    public function uninstall($plugin)
    {
        $plugin->uninstall();

        unset($this->options[\plugin_basename($plugin->filePath())]);

        $this->updateOptions();
    }

    public static function uninstallHook()
    {
        $pluguin = self::getInstance();
    }

    public static function __callStatic($name, $args)
    {
        
        add_action("admin_init",fn()=>var_dump($name));

        $chunks = explode("_", $name, 2);

        if (count($chunks) != 2) {
            throw new BadMethodCallException;
        }

        $function = $chunks[0];

        $basename = $chunks[1];

        $pluguin = self::getInstance();

        $plugin = $pluguin->getPlugin($basename);

        if (in_array($function, [
            "activate",
            "deactivate",
            "uninstall"
        ])) {
            $pluguin->{$function}($plugin);
        } else {
            throw new BadMethodCallException;
        }
    }

    public function getPlugin($basename)
    {
        return $this->plugins[$basename];
    }

    // private static function getBasename()
    // {
    //     return plugin_basename(dirname(__DIR__)."/pluguin.php");
    // }
}
