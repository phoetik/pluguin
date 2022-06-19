<?php

namespace Pluguin\Foundation;

use Closure;
use Pluguin\Container\Container;
use Pluguin\Contracts\Foundation\Plugin as PluginContract;

use Pluguin\Foundation\Plugin\Paths;
use Pluguin\Foundation\Plugin\ServiceProvider;

class Plugin extends Container implements PluginContract
{
    /**
     * The Pluguin framework version.
     *
     * @var string
     */
    const VERSION = '0.1.0';

    /**
     * The base path for the plugin.
     *
     * @var string
     */
    protected $basePath;

    /**
     * The custom plugin path defined by the developer.
     *
     * @var string
     */
    protected $pluginPath;

    /**
     * The custom database path defined by the developer.
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The custom language file path defined by the developer.
     *
     * @var string
     */
    protected $langPath;

    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;

    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;

    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;

    /**
     * The array of booting callbacks.
     *
     * @var callable[]
     */
    protected $bootingCallbacks = [];

    /**
     * The array of booted callbacks.
     *
     * @var callable[]
     */
    protected $bootedCallbacks = [];

    /**
     * The array of terminating callbacks.
     *
     * @var callable[]
     */
    protected $terminatingCallbacks = [];

    /**
     * Create a new Pluguin plugin instance.
     *
     * @param  string|null  $basePath
     * @return void
     */
    public function __construct($basePath = null)
    {
        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerBaseBindings();
        $this->registerBaseServiceProviders();
        $this->registerCoreContainerAliases();
    }

    /**
     * Set the base path for the plugin.
     *
     * @param  string  $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');

        $this->bindPathsInContainer();

        return $this;
    }

    /**
     * Bind all of the plugin paths in the container.
     *
     * @return void
     */
    protected function bindPathsInContainer()
    {
        $this->instance('path', $this->path());
        $this->instance('path.base', $this->basePath());
        $this->instance('path.config', $this->configPath());
        $this->instance('path.database', $this->databasePath());
        $this->instance('path.resources', $this->resourcePath());
        $this->instance('path.bootstrap', $this->bootstrapPath());

        $this->useLangPath((function () {
            if (is_dir($directory = $this->resourcePath('lang'))) {
                return $directory;
            }

            return $this->basePath('lang');
        })());
    }

    /**
     * Get the base path of the plugin installation.
     *
     * @param  string  $path
     * @return string
     */
    public function basePath($path = '')
    {
        return $this->basePath.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the plugin "plugin" directory.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path = '')
    {
        $pluginPath = $this->pluginPath ?: $this->basePath.DIRECTORY_SEPARATOR.'plugin';

        return $pluginPath.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the plugin directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function usePluginPath($path)
    {
        $this->pluginPath = $path;

        $this->instance('path', $path);

        return $this;
    }

    /**
     * Get the path to the bootstrap directory.
     *
     * @param  string  $path
     * @return string
     */
    public function bootstrapPath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'bootstrap'.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the plugin configuration files.
     *
     * @param  string  $path
     * @return string
     */
    public function configPath()
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'config.php';
    }

    /**
     * Get the path to the database directory.
     *
     * @param  string  $path
     * @return string
     */
    public function databasePath($path = '')
    {
        return ($this->databasePath ?: $this->basePath.DIRECTORY_SEPARATOR.'database').($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the database directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useDatabasePath($path)
    {
        $this->databasePath = $path;

        $this->instance('path.database', $path);

        return $this;
    }

    /**
     * Get the path to the language files.
     *
     * @param  string  $path
     * @return string
     */
    public function langPath($path = '')
    {
        return $this->langPath.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the language file directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useLangPath($path)
    {
        $this->langPath = $path;

        $this->instance('path.lang', $path);

        return $this;
    }

    /**
     * Get the path to the storage directory.
     *
     * @param  string  $path
     * @return string
     */
    public function storagePath($path = '')
    {
        return ($this->storagePath ?: $this->basePath.DIRECTORY_SEPARATOR.'storage')
                            .($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Set the storage directory.
     *
     * @param  string  $path
     * @return $this
     */
    public function useStoragePath($path)
    {
        $this->storagePath = $path;

        $this->instance('path.storage', $path);

        return $this;
    }

    /**
     * Get the path to the resources directory.
     *
     * @param  string  $path
     * @return string
     */
    public function resourcePath($path = '')
    {
        return $this->basePath.DIRECTORY_SEPARATOR.'resources'.($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Get the path to the views directory.
     *
     * This method returns the first configured path in the array of view paths.
     *
     * @param  string  $path
     * @return string
     */
    public function viewPath($path = '')
    {
        $basePath = $this->config['view']['paths'][0];

        return rtrim($basePath, DIRECTORY_SEPARATOR).($path != '' ? DIRECTORY_SEPARATOR.$path : '');
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('plugin', $this);

        $this->instance(Container::class, $this);
    }

    /**
     * Determine if the plugin is running with debug mode enabled.
     *
     * @return bool
     */
    public function hasDebugModeEnabled()
    {
        return (bool) $this->config['plugin']['debug'];
    }

    /**
     * Get the version number of the plugin.
     *
     * @return string
     */
    public function version()
    {
        return static::VERSION;
    }

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @param  bool  $raiseEvents
     * @return mixed
     */
    protected function resolve($abstract, $parameters = [], $raiseEvents = true)
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::resolve($abstract, $parameters, $raiseEvents);
    }

    

    /**
     * Resolve the given type from the container.
     *
     * @param  string  $abstract
     * @param  array  $parameters
     * @return mixed
     */
    public function make($abstract, array $parameters = [])
    {
        $this->loadDeferredProviderIfNeeded($abstract = $this->getAlias($abstract));

        return parent::make($abstract, $parameters);
    }

    /**
     * Determine if the given abstract type has been bound.
     *
     * @param  string  $abstract
     * @return bool
     */
    public function bound($abstract)
    {
        return $this->isDeferredService($abstract) || parent::bound($abstract);
    }

    /**
     * 
     */
    public function abort($code, $message = '', array $headers = [])
    {
        // @
    }

    /**
     * Register a terminating callback with the plugin.
     *
     * @param  callable|string  $callback
     * @return $this
     */
    public function terminating($callback)
    {
        $this->terminatingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Terminate the plugin.
     *
     * @return void
     */
    public function terminate()
    {
        $index = 0;

        while ($index < count($this->terminatingCallbacks)) {
            $this->call($this->terminatingCallbacks[$index]);

            $index++;
        }
    }

    /**
     * Get the wordpress locale
     *
     * @return string
     */
    public function getLocale()
    {
        return \get_user_locale();
    }
}