<?php

use Illuminate\Database\Seeder;

class DriverTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        // Insert default role
        DB::table('drivertype')->insert(
            array(
                array('iddrivertype'=>1,'name'=>'PKWT'),
                array('iddrivertype'=>2,'rolename'=>'PKWT BACKTUP'),
                array('iddrivertype'=>3,'rolename'=>'FREELANCE')
            )
        );

    }
}
