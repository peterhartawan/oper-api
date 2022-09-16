<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('b2c')
            ->create('paket', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('pricing_id', 11);
                $table->text('deskripsi_text');
                $table->text('deskripsi_list');
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
            ->dropIfExists('paket');
    }
}
