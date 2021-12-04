<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateLastOrderStatusOrderTask extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_tasks', function (Blueprint $table) {
            $table->renameColumn('last_updatestatus', 'last_update_status');
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
            $table->renameColumn('last_update_status', 'last_updatestatus');
        });
    }
}
