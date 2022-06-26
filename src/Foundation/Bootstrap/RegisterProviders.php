<?php

namespace Pluguin\Foundation\Bootstrap;

use Pluguin\Foundation\Plugin;

class RegisterProviders
{
    /**
     * Bootstrap the given plugin.
     *
     * @param  \Illuminate\Contracts\Foundation\Plugin  $plugin
     * @return void
     */
    public function bootstrap(Plugin $plugin)
    {
        $plugin->registerConfiguredProviders();
    }
}