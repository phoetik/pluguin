<?php

namespace Ravand\Core\Interfaces;

use Ravand\Core\Interfaces\Plugin\Hooks;

interface Plugin
{
    public function registerHooks(Hooks $hooks);
}