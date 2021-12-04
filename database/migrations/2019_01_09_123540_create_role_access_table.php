<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateRoleAccessTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('role_access', function(Blueprint $table)
		{
			$table->integer('role_access_id', true);
			$table->integer('idrole')->index('fk_role_has_menu_role1');
			$table->integer('idmenu')->index('fk_role_has_menu_menu1_idx');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('role_access');
	}

}
