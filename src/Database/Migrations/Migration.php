<?php

namespace Pluguin\Database\Migrations;

use Pluguin\Pluguin;
use Illuminate\Database\Migrations\Migration as AbstractMigration;

class Migration extends AbstractMigration
{
    protected $schema;

    public function __construct()
    {
        $this->schema = Pluguin::getInstance()->container["db.schema"];
    }
}