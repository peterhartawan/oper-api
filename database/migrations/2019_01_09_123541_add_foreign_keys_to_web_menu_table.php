<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToWebMenuTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('web_menu', function(Blueprint $table)
		{
			$table->foreign('parent_idmenu', 'fk_menu_menu1')->references('idmenu')->on('web_menu')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('web_menu', function(Blueprint $table)
		{
			$table->dropForeign('fk_menu_menu1');
		});
	}

}
