<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAllLatitudeLongitude extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order', function(Blueprint $table)
		{
			$table->string('origin_latitude')->nullable()->change();
			$table->string('origin_longitude')->nullable()->change();
			$table->string('destination_latitude')->nullable()->change();
			$table->string('destination_longitude')->nullable()->change();
        });

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->string('latitude')->nullable()->change();
            $table->string('longitude')->nullable()->change();
			$table->string('submit_latitude')->nullable()->change();
			$table->string('submit_longitude')->nullable()->change();
        });
        
		Schema::table('places', function(Blueprint $table)
		{
			$table->string('latitude')->nullable()->change();
			$table->string('longitude')->nullable()->change();
        });
        
		Schema::table('task', function(Blueprint $table)
		{
			$table->string('latitude')->nullable()->change();
			$table->string('longitude')->nullable()->change();
        });
        
        Schema::table('attendance', function (Blueprint $table) {
			$table->string('clock_in_latitude')->nullable()->change();
			$table->string('clock_in_longitude')->nullable()->change();
			$table->string('clock_out_latitude')->nullable()->change();
            $table->string('clock_out_longitude')->nullable()->change();
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
			$table->decimal('origin_latitude', 10, 0)->nullable()->change();
			$table->decimal('origin_longitude', 10, 0)->nullable()->change();
			$table->decimal('destination_latitude', 10, 0)->nullable()->change();
			$table->decimal('destination_longitude', 10, 0)->nullable()->change();
        });
        

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->decimal('latitude', 10, 0)->nullable()->change();
            $table->decimal('longitude', 10, 0)->nullable()->change();
			$table->decimal('submit_latitude', 10, 0)->nullable()->change();
			$table->decimal('submit_longitude', 10, 0)->nullable()->change();
        });
        

		Schema::table('places', function(Blueprint $table)
		{
			$table->decimal('latitude', 10, 0)->nullable()->change();
			$table->decimal('longitude', 10, 0)->nullable()->change();
        });
        
		Schema::table('task', function(Blueprint $table)
		{
			$table->decimal('latitude', 10, 0)->nullable()->change();
			$table->decimal('longitude', 10, 0)->nullable()->change();
        });
        
        Schema::table('attendance', function (Blueprint $table) {
			$table->decimal('clock_in_latitude', 10, 0)->nullable()->change();
			$table->decimal('clock_in_longitude', 10, 0)->nullable()->change();
			$table->decimal('clock_out_latitude', 10, 0)->nullable()->change();
            $table->decimal('clock_out_longitude', 10, 0)->nullable()->change();
        });
    }
}
