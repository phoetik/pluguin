<?php

namespace Pluguin\Contracts\Hooks;

use \Closure;

interface Action extends Hook
{
    public function run(...$args);
}