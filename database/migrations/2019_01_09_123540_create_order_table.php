<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateOrderTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('order', function(Blueprint $table)
		{
			$table->integer('idorder', true);
			$table->integer('task_template_task_template_id')->index('fk_order_task_template1_idx');
			$table->integer('client_userid')->unsigned()->nullable()->index('fk_order_users1_idx');
			$table->integer('driver_userid')->unsigned()->nullable()->index('fk_order_users2_idx');
			$table->integer('dispatcher_userid')->unsigned()->nullable()->index('fk_order_users3_idx');
			$table->boolean('order_status');
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->timestamps();
			$table->boolean('status')->nullable();
			$table->dateTime('booking_time')->nullable();
			$table->decimal('origin_latitude', 10, 0)->nullable();
			$table->decimal('origin_longitude', 10, 0)->nullable();
			$table->decimal('destination_latitude', 10, 0)->nullable();
			$table->decimal('destination_longitude', 10, 0)->nullable();
			$table->string('client_vehicle_license', 10)->nullable();
			$table->string('vehicle_brand', 45)->nullable();
			$table->string('vehicle_type', 45)->nullable();
			$table->string('vehicle_transmission', 45)->nullable();
			$table->text('message')->nullable();
			$table->string('order_number', 45)->nullable();
			$table->integer('order_type_idorder_type')->index('fk_order_order_type2_idx');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('order');
	}

}
