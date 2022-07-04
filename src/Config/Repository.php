<?php

namespace Pluguin\Config;

use \Illuminate\Config\Repository as Config;

class Repository extends Config
{
    public function getItems()
    {
        return $this->items;
    }

    public function setItems(array $items)
    {
        $this->items = $items;
    }
}