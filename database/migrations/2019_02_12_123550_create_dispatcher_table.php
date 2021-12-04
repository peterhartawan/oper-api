<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDispatcherTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('dispatcher', function(Blueprint $table)
		{
			$table->integer('iddispatcher', true);
			$table->integer('users_id')->unsigned()->index('fk_dispatcher_users1_idx');
			$table->string('nik',50)->nullable();
			$table->date('birthdate');	
			$table->boolean('gender')->nullable();
			$table->string('address', 45);
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
		Schema::drop('dispatcher');
	}

}
