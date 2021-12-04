<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToRoleAccessTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('role_access', function(Blueprint $table)
		{
			$table->foreign('idmenu', 'fk_role_has_menu_menu1')->references('idmenu')->on('web_menu')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('idrole', 'fk_role_has_menu_role1')->references('idrole')->on('role')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('role_access', function(Blueprint $table)
		{
			$table->dropForeign('fk_role_has_menu_menu1');
			$table->dropForeign('fk_role_has_menu_role1');
		});
	}

}
