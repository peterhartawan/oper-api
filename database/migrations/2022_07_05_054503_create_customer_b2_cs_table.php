<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCustomerB2CsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_b2_cs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('phone', 15);
            $table->string('email', 45);
            $table->string('fullname', 45);
            $table->int('gender', 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_b2_cs');
    }
}
