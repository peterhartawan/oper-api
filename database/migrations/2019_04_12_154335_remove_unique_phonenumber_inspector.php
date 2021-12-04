<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveUniquePhonenumberInspector extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inspector', function(Blueprint $table)
        {
            $table->dropUnique('phonenumber_UNIQUE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inspector', function(Blueprint $table)
        {
            $table->unique('phonenumber');

        });
    }
}
