<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('role')->insert(
            array(
                array('idrole'=>1,'rolename'=>'Super Admin', 'roletype'=> 'super_admin'),
                array('idrole'=>2,'rolename'=>'Admin Vendor'     , 'roletype'=> 'admin_vendor'),
                array('idrole'=>3,'rolename'=>'Client Enterprise', 'roletype'=> 'client_enterprise'),
                array('idrole'=>4,'rolename'=>'Dispatcher Enterprise', 'roletype'=> 'dispatcher_enterprise'),
                array('idrole'=>5,'rolename'=>'Dispatcher Enterprise Plus', 'roletype'=> 'dispatcher_enterprise_plus'),
                array('idrole'=>6,'rolename'=>'Dispatcher On Demand', 'roletype'=> 'dispatcher_ondemand'),
                array('idrole'=>7,'rolename'=>'Driver', 'roletype'=> 'driver'),
                array('idrole'=>8,'rolename'=>'Employee', 'roletype'=> 'employee')
            )
        );
    }
}
