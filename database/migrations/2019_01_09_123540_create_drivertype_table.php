<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateDrivertypeTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('drivertype', function(Blueprint $table)
		{
			$table->integer('iddrivertype', true);
			$table->string('name', 45)->comment('0 : PKWT
1 : PKWT BACKTUP
2 : FREELANCE
');
			$table->text('descriptuon', 65535)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('drivertype');
	}

}
