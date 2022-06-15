<?php

namespace Ravand\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database
{
    public static function init()
    {
        global $table_prefix;

        $capsule = new Capsule;

        $capsule->addConnection([
            'driver' => 'mysql',
            'host' => DB_HOST,
            'database' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD,
            'charset' => DB_CHARSET,
            'prefix' => $table_prefix,
        ]);

        $capsule->setAsGlobal();

        $capsule->bootEloquent();
    }
}
