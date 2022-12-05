me<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMonthlyBaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('monthly_base_order', function (Blueprint $table) {
            $table->increments('id');
            $table->string('client_vehicle_license');
            $table->string('user_fullname');
            $table->string('user_phonenumber');
            $table->integer('vehicle_brand_id');
            $table->string('vehicle_type');
            $table->string('vehicle_transmission');
            $table->string('message');
            $table->string('origin_name');
            $table->string('origin_latitude');
            $table->integer('driver_userid');
            $table->integer('times_a_week');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('monthly_base_order');
    }
}
