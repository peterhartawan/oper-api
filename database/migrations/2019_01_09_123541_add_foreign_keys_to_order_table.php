<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToOrderTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('order', function(Blueprint $table)
		{
			$table->foreign('order_type_idorder_type', 'fk_order_order_type2')->references('idorder_type')->on('order_type')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('task_template_task_template_id', 'fk_order_task_template1')->references('task_template_id')->on('task_template')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('client_userid', 'fk_order_users1')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('driver_userid', 'fk_order_users2')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('dispatcher_userid', 'fk_order_users3')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('order', function(Blueprint $table)
		{
			$table->dropForeign('fk_order_order_type2');
			$table->dropForeign('fk_order_task_template1');
			$table->dropForeign('fk_order_users1');
			$table->dropForeign('fk_order_users2');
			$table->dropForeign('fk_order_users3');
		});
	}

}
