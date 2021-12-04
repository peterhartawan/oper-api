<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToDriverTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('driver', function(Blueprint $table)
		{
			$table->foreign('drivertype_iddrivertype', 'fk_driver_drivertype1')->references('iddrivertype')->on('drivertype')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('users_id', 'fk_driver_users1')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('driver', function(Blueprint $table)
		{
			$table->dropForeign('fk_driver_drivertype1');
			$table->dropForeign('fk_driver_users1');
		});
	}

}
