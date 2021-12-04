<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToClientEnterpriseTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('client_enterprise', function(Blueprint $table)
		{
			$table->foreign('enterprise_type_identerprise_type', 'fk_client_enterprise_enterprise_type1')->references('identerprise_type')->on('enterprise_type')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('vendor_idvendor', 'fk_client_enterprise_vendor1')->references('idvendor')->on('vendor')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('client_enterprise', function(Blueprint $table)
		{
			$table->dropForeign('fk_client_enterprise_enterprise_type1');
			$table->dropForeign('fk_client_enterprise_vendor1');
		});
	}

}
