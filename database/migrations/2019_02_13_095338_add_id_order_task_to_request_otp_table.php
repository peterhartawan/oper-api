<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdOrderTaskToRequestOtpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('request_otp', function (Blueprint $table) {
            $table->integer('idordertask')->index('fk_idordertask_idx');
			$table->foreign('idordertask', 'fk_idordertask')->references('idordertask')->on('order_tasks')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('request_otp', function (Blueprint $table) {
			$table->dropForeign('fk_idordertask');
            $table->dropColumn(['idordertask']);
        });
    }
}
