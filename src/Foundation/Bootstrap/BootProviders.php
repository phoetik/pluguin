<?php

namespace Pluguin\Foundation\Bootstrap;

use Pluguin\Contracts\Foundation\Plugin;

class BootProviders
{
    /**
     * Bootstrap the given plugin.
     *
     * @param  Pluguin\Contracts\Foundation\Plugin  $plugin
     * @return void
     */
    public function bootstrap(Plugin $plugin)
    {
        $plugin->boot();
    }
}