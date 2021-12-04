<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateLaatitudeLongitudeEmployeeAndDriver extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('driver', function (Blueprint $table) {          
			$table->string('attendance_latitude')->nullable()->change();
			$table->string('attendance_longitude')->nullable()->change();		
        });

        Schema::table('employee', function (Blueprint $table) {          
			$table->string('attendance_latitude')->nullable()->change();
			$table->string('attendance_longitude')->nullable()->change();		
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('driver', function (Blueprint $table) {          
			$table->decimal('attendance_latitude', 10, 0)->nullable();
			$table->decimal('attendance_longitude', 10, 0)->nullable();		
        });
        
        Schema::table('employee', function (Blueprint $table) {          
			$table->decimal('attendance_latitude', 10, 0)->nullable();
			$table->decimal('attendance_longitude', 10, 0)->nullable();		
        });

       
    }
}
