<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToOrderTasksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->foreign('inspector_idinspector', 'fk_order_tasks_inspector1')->references('idinspector')->on('inspector')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('order_idorder', 'fk_order_tasks_order1')->references('idorder')->on('order')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('task_idtask', 'fk_order_tasks_task1')->references('idtask')->on('task')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->dropForeign('fk_order_tasks_inspector1');
			$table->dropForeign('fk_order_tasks_order1');
			$table->dropForeign('fk_order_tasks_task1');
		});
	}

}
