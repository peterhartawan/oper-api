<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateTableTracking extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tracking_attendance', function (Blueprint $table) {          
			$table->string('latitude', 50)->nullable()->change();
			$table->string('longitude', 50)->nullable()->change();		
        });

        Schema::table('tracking_task', function (Blueprint $table) {          
			$table->string('latitude', 50)->nullable()->change();
			$table->string('longitude', 50)->nullable()->change();		
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tracking_attendance', function (Blueprint $table) {          
			$table->decimal('latitude', 10, 0)->nullable();
			$table->decimal('longitude', 10, 0)->nullable();		
        });
        
        Schema::table('tracking_task', function (Blueprint $table) {          
			$table->decimal('latitude', 10, 0)->nullable();
			$table->decimal('longitude', 10, 0)->nullable();			
        });
    }
}
