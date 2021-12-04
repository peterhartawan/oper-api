<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateAllTask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_template', function(Blueprint $table)
		{
			$table->string('task_template_name', 100)->change();
        });

        Schema::table('task', function(Blueprint $table)
		{
			$table->string('name', 100)->change();
			$table->string('description', 200)->nullable()->change();
        });

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->string('name', 100)->change();
			$table->string('description', 200)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn('identerprise');
            $table->dropColumn('places_type');
        });

        Schema::table('task_template', function(Blueprint $table)
		{
			$table->dropColumn('task_template_name');
        });

        Schema::table('task', function(Blueprint $table)
		{
			$table->dropColumn('name');
			$table->dropColumn('description');
        });

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->dropColumn('name');
			$table->dropColumn('description');
        });
    }
}
