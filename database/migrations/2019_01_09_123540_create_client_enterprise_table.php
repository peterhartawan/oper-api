<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateClientEnterpriseTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('client_enterprise', function(Blueprint $table)
		{
			$table->integer('identerprise', true);
			$table->string('name', 45);
			$table->string('description', 45)->nullable();
			$table->integer('enterprise_type_identerprise_type')->index('fk_client_enterprise_enterprise_type1_idx');
			$table->string('email', 191)->unique();
			$table->string('office_phone', 191);
			$table->text('office_address');
			$table->text('pic_name');
			$table->string('pic_phone', 191);
			$table->boolean('is_private')->default(0);
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->default(1);
			$table->integer('vendor_idvendor')->index('fk_client_enterprise_vendor1_idx');
			$table->string('site_url', 100)->nullable();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('client_enterprise');
	}

}
