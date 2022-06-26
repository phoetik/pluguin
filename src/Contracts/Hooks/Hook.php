<?php

namespace Pluguin\Contracts\Hooks;

interface Hook
{
    public function name();

    public function type();

    public static function register(Closure $callback);
}
