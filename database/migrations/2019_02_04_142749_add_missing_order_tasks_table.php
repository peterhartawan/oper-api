<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddMissingOrderTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
			$table->integer('sequence')->nullable()->default(0);
			$table->string('name', 45);
			$table->string('description', 45)->nullable();
			$table->boolean('is_required')->nullable()->default(1);
			$table->boolean('is_need_photo')->nullable()->default(0);
			$table->boolean('is_need_inspector_validation')->nullable()->default(0);
			$table->decimal('latitude', 10, 0)->nullable();
            $table->decimal('longitude', 10, 0)->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->dropColumn(['sequence', 'name','description','is_required','is_need_photo','is_need_inspector_validation','latitude','longitude']);
        });
    }
}
