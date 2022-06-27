<?php

namespace Pluguin\Contracts\Hooks;

interface Callback
{
    public function disable();

    public function enable();

    public function register($callback);

    public function extend($callback);

    public function before($callback);

    public function after($callback);

    public function __invoke(...$args);
}
