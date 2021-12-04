<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderTasksTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order_tasks', function(Blueprint $table)
		{
			$table->integer('idordertask', true);
			$table->integer('task_idtask')->index('fk_order_tasks_task1_idx');
			$table->integer('order_idorder')->index('fk_order_tasks_order1_idx');
			$table->text('attachment_url')->nullable();
			$table->boolean('order_task_status')->nullable();
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->nullable();
			$table->string('inspector_otp', 45)->nullable();
			$table->decimal('submit_latitude', 10, 0)->nullable();
			$table->decimal('submit_longitude', 10, 0)->nullable();
			$table->integer('inspector_idinspector')->index('fk_order_tasks_inspector1_idx');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('order_tasks');
	}

}
