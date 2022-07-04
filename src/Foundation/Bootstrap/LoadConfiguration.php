<?php

namespace Pluguin\Foundation\Bootstrap;

use Exception;
use Illuminate\Config\Repository;

class LoadConfiguration
{
    /**
     * Bootstrap the given plugin.
     *
     * @param  \Pluguin\Contracts\Foundation\Plugin  $plugin
     * @return void
     */
    public function bootstrap($plugin)
    {
        $items = [];
        if (file_exists($config = $plugin->configFile())) {
            $items = require $config;
        }

        $plugin->instance('config', $config = new Repository($items));
    }
}