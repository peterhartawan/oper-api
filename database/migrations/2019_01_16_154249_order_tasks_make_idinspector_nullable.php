<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class OrderTasksMakeIdinspectorNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {	
        Schema::drop('order_tasks');
        
		Schema::create('order_tasks', function(Blueprint $table)
		{
			$table->integer('idordertask', true);
			$table->integer('task_idtask')->index('fk_order_tasks_task1_idx');
			$table->integer('order_idorder')->index('fk_order_tasks_order1_idx');
			$table->text('attachment_url')->nullable();
			$table->boolean('order_task_status')->nullable();
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->nullable();
			$table->string('inspector_otp', 45)->nullable();
			$table->decimal('submit_latitude', 10, 0)->nullable();
			$table->decimal('submit_longitude', 10, 0)->nullable();
			$table->integer('inspector_idinspector')->nullable()->index('fk_order_tasks_inspector1_idx');
        });

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->foreign('inspector_idinspector', 'fk_order_tasks_inspector1')->references('idinspector')->on('inspector')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('order_idorder', 'fk_order_tasks_order1')->references('idorder')->on('order')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('task_idtask', 'fk_order_tasks_task1')->references('idtask')->on('task')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
   }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::drop('order_tasks');
        
		Schema::create('order_tasks', function(Blueprint $table)
		{
			$table->integer('idordertask', true);
			$table->integer('task_idtask')->index('fk_order_tasks_task1_idx');
			$table->integer('order_idorder')->index('fk_order_tasks_order1_idx');
			$table->text('attachment_url')->nullable();
			$table->boolean('order_task_status')->nullable();
			$table->timestamps();
			$table->integer('created_by')->nullable();
			$table->integer('updated_by')->nullable();
			$table->boolean('status')->nullable();
			$table->string('inspector_otp', 45)->nullable();
			$table->decimal('submit_latitude', 10, 0)->nullable();
			$table->decimal('submit_longitude', 10, 0)->nullable();
			$table->integer('inspector_idinspector')->index('fk_order_tasks_inspector1_idx');
        });

        Schema::table('order_tasks', function(Blueprint $table)
		{
			$table->foreign('inspector_idinspector', 'fk_order_tasks_inspector1')->references('idinspector')->on('inspector')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('order_idorder', 'fk_order_tasks_order1')->references('idorder')->on('order')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('task_idtask', 'fk_order_tasks_task1')->references('idtask')->on('task')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
    }
}
