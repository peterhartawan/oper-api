<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateWebMenuTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('web_menu', function(Blueprint $table)
		{
			$table->integer('idmenu', true);
			$table->string('name', 191);
			$table->string('slug', 191)->nullable();
			$table->integer('parent_idmenu')->nullable()->index('fk_menu_menu1_idx');
			$table->boolean('status')->default(1);
			$table->integer('sequence')->nullable();
			$table->string('icon')->nullable();	
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('web_menu');
	}

}
