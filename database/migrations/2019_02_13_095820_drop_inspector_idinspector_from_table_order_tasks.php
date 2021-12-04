<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropInspectorIdinspectorFromTableOrderTasks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->dropForeign('fk_order_tasks_inspector1');
            $table->dropColumn(['inspector_idinspector','inspector_otp']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->string('inspector_otp', 45)->nullable();
			$table->integer('inspector_idinspector')->nullable()->index('fk_order_tasks_inspector1_idx');
			$table->foreign('inspector_idinspector', 'fk_order_tasks_inspector1')->references('idinspector')->on('inspector')->onUpdate('NO ACTION')->onDelete('NO ACTION');
	   });
    }
}
