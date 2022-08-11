<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKuponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kupons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('promo_id');
            $table->integer('customer_id');
            $table->integer('jumlah_kupon');
            $table->date('waktu_berakhir');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kupons');
    }
}
