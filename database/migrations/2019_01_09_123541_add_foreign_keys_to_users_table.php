<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToUsersTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$table->foreign('client_enterprise_identerprise', 'fk_role_client_enterprise1')->references('identerprise')->on('client_enterprise')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('vendor_idvendor', 'fk_role_vendor1')->references('idvendor')->on('vendor')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('idrole', 'fk_user_role1')->references('idrole')->on('role')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$table->dropForeign('fk_role_client_enterprise1');
			$table->dropForeign('fk_role_vendor1');
			$table->dropForeign('fk_user_role1');
		});
	}

}
