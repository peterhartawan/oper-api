<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateColumnLocationName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task', function (Blueprint $table) {
			$table->string('location_name', 300)->change();
        });
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->string('location_name', 300)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task', function (Blueprint $table) {
			$table->dropColumn('location_name');
        });
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->dropColumn('location_name');
        });
    }
}
