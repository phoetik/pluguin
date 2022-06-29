<?php

namespace Pluguin\Migrations;

use Illuminate\Database\Migrations\Migration;
use Pluguin\Database\Database;
use Illuminate\Database\Schema\Blueprint;

class CreateLosersTable extends Migration
{
    public function up()
    {
        Database::schema()->create('losers', function (Blueprint $table) {
            $table->id();
            $table->string('loser_name');
        });
    }

    public function down()
    {
        Database::schema()->dropIfExists('losers');
    }
}
