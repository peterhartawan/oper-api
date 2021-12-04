<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTableEmployee extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee', function (Blueprint $table) {
            $table->integer('idemployee', true);
			$table->integer('users_id')->unsigned()->index('fk_driver_users1_idx');
			$table->string('nik',50)->nullable();
			$table->string('job_position', 45);
			$table->date('birthdate');
            $table->boolean('gender')->nullable();
			$table->string('address', 45);
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('is_on_task')->default(0);
			$table->boolean('status')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee');
    }
}
