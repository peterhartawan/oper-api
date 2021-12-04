<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeDataInVarchar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
			$table->string('name', 45)->change();
			$table->string('email', 100)->change();
        });
        Schema::table('client_enterprise', function (Blueprint $table) {
			$table->text('description')->nullable()->change();
			$table->string('email', 100)->change();
            $table->string('pic_name', 45)->change();
			$table->string('pic_email', 100)->change();
			$table->string('office_phone', 45)->change();
			$table->string('pic_phone', 45)->change();
        });
        Schema::table('driver', function (Blueprint $table) {
			$table->text('address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
			$table->string('name', 191)->change();
			$table->string('email', 191)->change();
        });
        Schema::table('client_enterprise', function (Blueprint $table) {
			$table->string('description', 45)->nullable()->change();
			$table->string('email', 191)->change();
			$table->text('pic_name')->change();
			$table->string('pic_email', 191)->change();
			$table->string('office_phone', 191)->change();
			$table->string('pic_phone', 191)->change();
        });
        Schema::table('driver', function (Blueprint $table) {
			$table->text('address')->nullable()->change();
        });
    }
}
