<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropForeignkeyTaskIdtask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->dropForeign('fk_order_tasks_task1');
            $table->dropColumn('task_idtask');
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
			$table->integer('task_idtask')->index('fk_order_tasks_task1_idx');
            $table->foreign('task_idtask', 'fk_order_tasks_task1')->references('idtask')->on('task')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }
}
