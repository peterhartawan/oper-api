<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class MobileVersionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('mobile_version')->insert(
            array(
                array('id'=>1, 'device_type' => 'android', 'version'=>'7', 'created_by'=> 1),
            )
        );
    }
}
