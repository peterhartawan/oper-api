<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropForeignkeyDispatcherVendorIdvendor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('driver', function (Blueprint $table) {
            $table->dropColumn('dispatcher_vendor_idvendor');
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
			$table->integer('dispatcher_vendor_idvendor')->index('fk_driver_dispatcher1');
            $table->foreign('dispatcher_vendor_idvendor', 'fk_driver_dispatcher1_idx')->references('idvendor')->on('vendor')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }
}
