<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOpsHoursTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('b2c')
            ->create('ops_hours', function (Blueprint $table) {
                $table->increments('id');
                $table->varchar('nama', 20);
                $table->time('jam');
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('b2c')
            ->dropIfExists('ops_hours');
    }
}
