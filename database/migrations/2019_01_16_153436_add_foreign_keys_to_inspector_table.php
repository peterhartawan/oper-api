<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToInspectorTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('inspector', function(Blueprint $table)
		{
			$table->integer('client_enterprise_identerprise')->index('fk_inspector_client_enterprise1_idx');
			$table->foreign('client_enterprise_identerprise', 'fk_inspector_client_enterprise1')->references('identerprise')->on('client_enterprise')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('inspector', function(Blueprint $table)
		{
			$table->dropForeign('fk_inspector_client_enterprise1');
            $table->dropColumn(['client_enterprise_identerprise']);
		});
	}

}
