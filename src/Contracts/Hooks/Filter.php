<?php

namespace Pluguin\Contracts\Hooks;

use \Closure;

interface Filter extends Hook
{
    public function apply($value, ...$args);
}