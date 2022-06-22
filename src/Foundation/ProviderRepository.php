<?php

namespace Illuminate\Foundation;

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
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * The path to the manifest file.
     *
     * @var string
     */
    protected $manifestPath;

    /**
     * Create a new service repository instance.
     *
     * @param  Pluguin\Contracts\Foundation\Plugin  $plugin
     * @param  \Illuminate\Filesystem\Filesystem  $files
     * @param  string  $manifestPath
     * @return void
     */
    public function __construct(PluginContract $plugin, Filesystem $files, $manifestPath)
    {
        $this->plugin = $plugin;
        $this->files = $files;
        $this->manifestPath = $manifestPath;
    }

    /**
     * Register the application service providers.
     *
     * @param  array  $providers
     * @return void
     */
    public function load(array $providers)
    {
        $manifest = $this->loadManifest();

        // First we will load the service manifest, which contains information on all
        // service providers registered with the application and which services it
        // provides. This is used to know which services are "deferred" loaders.
        if ($this->shouldRecompile($manifest, $providers)) {
            $manifest = $this->compileManifest($providers);
        }

        foreach ($manifest['eager'] as $provider) {
            $this->plugin->register($provider);
        }
    }

    /**
     * Load the service provider manifest JSON file.
     *
     * @return array|null
     */
    public function loadManifest()
    {
        if ($this->files->exists($this->manifestPath)) {
            $manifest = $this->files->getRequire($this->manifestPath);

            if ($manifest) {
                return $manifest;
            }
        }
    }

    /**
     * Determine if the manifest should be compiled.
     *
     * @param  array  $manifest
     * @param  array  $providers
     * @return bool
     */
    public function shouldRecompile($manifest, $providers)
    {
        return is_null($manifest) || $manifest['providers'] != $providers;
    }

    /**
     * Compile the plugin service manifest file.
     *
     * @param  array  $providers
     * @return array
     */
    protected function compileManifest($providers)
    {
        $manifest = $this->freshManifest($providers);

        foreach ($providers as $provider) {
            $instance = $this->createProvider($provider);

            $manifest['eager'][] = $provider;
        }

        return $this->writeManifest($manifest);
    }

    /**
     * Create a fresh service manifest data structure.
     *
     * @param  array  $providers
     * @return array
     */
    protected function freshManifest(array $providers)
    {
        return [
            'providers' => $providers, 
            'eager' => []
        ];
    }

    /**
     * Write the service manifest file to disk.
     *
     * @param  array  $manifest
     * @return array
     *
     * @throws \Exception
     */
    public function writeManifest($manifest)
    {
        if (! is_writable($dirname = dirname($this->manifestPath))) {
            throw new Exception("The {$dirname} directory must be present and writable.");
        }

        $this->files->replace(
            $this->manifestPath, '<?php return '.var_export($manifest, true).';'
        );

        return $manifest;
    }

    /**
     * Create a new provider instance.
     *
     * @param  string  $provider
     * @return Pluguin\Support\ServiceProvider
     */
    public function createProvider($provider)
    {
        return new $provider($this->plugin);
    }
}