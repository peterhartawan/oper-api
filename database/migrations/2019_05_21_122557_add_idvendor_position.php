<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdvendorPosition extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_position', function (Blueprint $table) {  
            $table->integer('vendor_idvendor')->nullable()->index('fk_vendor_idvendor_idx2');
            $table->foreign('vendor_idvendor', 'fk_vendor_idvendor2')->references('idvendor')->on('vendor')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_position', function (Blueprint $table) {
			$table->dropForeign('fk_vendor_idvendor');
            $table->dropColumn('vendor_idvendor');
        });
    }
}
