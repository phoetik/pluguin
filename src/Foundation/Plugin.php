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

    use HasPaths;

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