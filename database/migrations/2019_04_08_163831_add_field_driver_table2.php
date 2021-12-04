<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFieldDriverTable2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('driver', function (Blueprint $table) {          
			$table->decimal('attendance_latitude', 10, 0)->nullable();
			$table->decimal('attendance_longitude', 10, 0)->nullable();		
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
            $table->dropColumn('attendance_latitude');
            $table->dropColumn('attendance_longitude');
        });
    }
}
