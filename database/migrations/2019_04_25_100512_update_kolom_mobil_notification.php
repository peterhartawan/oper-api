<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateKolomMobilNotification extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_notification', function (Blueprint $table) {
			$table->string('device_type',100)->nullable()->change();
			$table->text('device_info')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_notification', function (Blueprint $table) {
			$table->string('device_type',100);
			$table->text('device_info');
        });
    }
}
