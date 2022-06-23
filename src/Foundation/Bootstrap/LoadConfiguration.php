<?php

namespace Illuminate\Foundation\Bootstrap;

use Exception;
use Illuminate\Config\Repository;

class LoadConfiguration
{
    /**
     * Bootstrap the given plugin.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return void
     */
    public function bootstrap($app)
    {
        $items = [];
        if (file_exists($config = $app->configFile())) {
            $items = require $config;
        }

        $app->instance('config', $config = new Repository($items));
    }
}