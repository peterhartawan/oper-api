<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIdstatisContentTableWebmenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('web_menu', function (Blueprint $table) {
            $table->integer('static_content_idstatic_content')->unsigned()->index('fk_idstatic_content_idx')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('web_menu', function (Blueprint $table) {
            $table->dropColumn('static_content_idstatic_content');
        });
    }
}
