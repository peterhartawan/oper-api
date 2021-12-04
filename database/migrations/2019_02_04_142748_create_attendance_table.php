<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('users_id')->unsigned()->index('fk_attendance_users_idx');
            $table->dateTime('clock_in');
			$table->decimal('clock_in_latitude', 10, 0)->nullable();
			$table->decimal('clock_in_longitude', 10, 0)->nullable();
            $table->dateTime('clock_out')->nullable();
			$table->decimal('clock_out_latitude', 10, 0)->nullable();
            $table->decimal('clock_out_longitude', 10, 0)->nullable();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
            
            $table->timestamps();
        });

        Schema::table('attendance', function(Blueprint $table)
		{
			$table->foreign('users_id', 'fk_attendance_users_idx')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('attendance', function(Blueprint $table)
		{
			$table->dropForeign('fk_attendance_users_idx');
        });
        
        Schema::dropIfExists('attendance');
    }
}
