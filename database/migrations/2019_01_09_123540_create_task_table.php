<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTaskTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('task', function(Blueprint $table)
		{
			$table->integer('idtask', true);
			$table->integer('sequence')->nullable()->default(0);
			$table->string('name', 45);
			$table->string('description', 45)->nullable();
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->default(1);
			$table->integer('task_template_id')->index('task_task_template_id_foreign');
			$table->boolean('is_required')->nullable()->default(1);
			$table->boolean('is_need_photo')->nullable()->default(0);
			$table->boolean('is_need_inspector_validation')->nullable()->default(0);
			$table->decimal('latitude', 10, 0)->nullable();
			$table->decimal('longitude', 10, 0)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('task');
	}

}
