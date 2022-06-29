<?php

namespace Pluguin;

use Pluguin\Database\Database;

class Pluguin
{
    protected static $instance;

    protected $database;

    private function __construct()
    {
        $this->initDatabase();

        \add_action("plugins_loaded", function () {

            \do_action("pluguin", $this);

        });

    }

    protected function initDatabase()
    {
        $this->database = new Database;
    }

    public function database()
    {
        return $this->database;
    }

    public static function init()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
    }

    public static function getInstance()
    {
        return static::$instance;
    }
}
