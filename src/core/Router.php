<?php

namespace Ravand\Core;

use Ravand\Core\Traits\Singleton;

abstract class Router
{
    use Singleton;

    private $routes = [];

    public function loadAddOnRoutes() {}

    public function loadRoutes()
    {
        require_once __DIR__ . "/../routes/$this->name.php";
    }

    public function makeCallback($controller, $function, $middlewares, $name)
    {
        return function()use($controller){
            echo \plugin_basename(__FILE__);
        };
    }

}