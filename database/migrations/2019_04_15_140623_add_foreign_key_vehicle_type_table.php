<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeignKeyVehicleTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicle_type', function (Blueprint $table) {
            $table->foreign('vehicle_brand_id')->references('id')->on('vehicle_brand');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicle_type', function (Blueprint $table) {
			$table->dropForeign('fk_vehicle_brand1_idx');
        });
    }
}
