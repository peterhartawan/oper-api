<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDriverTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('driver', function(Blueprint $table)
		{
			$table->integer('iddriver', true);
			$table->integer('users_id')->unsigned()->index('fk_driver_users1_idx');
			$table->date('birthdate');
			$table->string('address', 45);
			$table->integer('drivertype_iddrivertype')->index('fk_driver_drivertype1_idx');
			$table->integer('dispatcher_vendor_idvendor')->index('fk_driver_dispatcher1_idx');
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->default(1);
			$table->string('insurance_policy_number', 45)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('driver');
	}

}
