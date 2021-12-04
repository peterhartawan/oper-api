<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateVendorTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('vendor', function(Blueprint $table)
		{
			$table->integer('idvendor', true);
			$table->string('name', 45);
			$table->string('email', 191)->unique();
			$table->string('office_phone_number', 191);
			$table->text('office_address', 65535);
			$table->string('pic_name', 191);
			$table->string('pic_mobile_number', 191);
			$table->string('pic_email', 191)->unique();
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->default(1);
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('vendor');
	}

}
