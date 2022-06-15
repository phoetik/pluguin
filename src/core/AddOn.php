<?php

namespace Ravand\Core;

use \Ravand;

abstract class AddOn
{
    private Ravand $core;

    final private function __construct(Ravand $plugin)
    {
        $this->core = $plugin;

        $this->root = "";
    }
}
