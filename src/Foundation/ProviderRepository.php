<?php

namespace Pluguin\Foundation;

use Exception;
use Pluguin\Contracts\Foundation\Plugin as PluginContract;
use Illuminate\Filesystem\Filesystem;

class ProviderRepository
{
    /**
     * The plugin implementation.
     *
     * @var Pluguin\Contracts\Foundation\Plugin
     */
    protected $plugin;

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifest;

    /**
     * Create a new service repository instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Plugin  $plugin
     * @param  array  $manifest
     * @return void
     */
    public function __construct(PluginContract $plugin, $manifest = [])
    {
        $this->plugin = $plugin;
        $this->manifest = $manifest;
    }

    /**
     * Register the application service providers.
     *
     * @param  array  $providers
     * @return void
     */
    public function load()
    {
        $manifest = $this->loadManifest();

        // Next, we will register events to load the providers for each of the events
        // that it has requested. This allows the service provider to defer itself
        // while still getting automatically loaded when a certain event occurs.
        foreach ($manifest['when'] ?? [] as $provider => $events) {
            $this->registerLoadEvents($provider, $events);
        }

        // We will go ahead and register all of the eagerly loaded providers with the
        // application so their services can be registered with the application as
        // a provided service. Then we will set the deferred service list on it.
        foreach ($manifest['eager'] ?? [] as $provider) {
            $this->plugin->register($provider);
        }

        $deferred = [];

        foreach ($manifest["deferred"] ?? [] as $provider => $services) {
            if (is_array($services)) {
                foreach ($services as $service) {
                    $deferred[$service] = $provider;
                }
            } else {
                $deferred[$services] = $provider;
            }
        }

        $this->plugin->addDeferredServices($deferred);
    }

    /**
     * Load the service provider manifest
     *
     * @return array|null
     */
    public function loadManifest()
    {
        // The service manifest is a file containing a JSON representation of every
        // service provided by the application and whether its provider is using
        // deferred loading or should be eagerly loaded on each request to us.

        return empty($this->manifest) ? [
            'eager' => [],
            'deferred' => [],
            'when' => []
        ] : $this->manifest;
    }

    /**
     * Register the load events for the given provider.
     *
     * @param  string  $provider
     * @param  array  $events
     * @return void
     */
    protected function registerLoadEvents($provider, array $events)
    {
        if (count($events) < 1) {
            return;
        }

        $this->plugin->make('events')->listen($events, function () use ($provider) {
            $this->plugin->register($provider);
        });
    }

    /**
     * Create a new provider instance.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->plugin);
    }
}