<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePricingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('b2c')
            ->create('pricing', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama', 20);
                $table->integer('harga', 11);
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('b2c')->dropIfExists('pricings');
    }
}
