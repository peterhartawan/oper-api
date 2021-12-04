<?php

use Illuminate\Database\Seeder;

class RoleAccessTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('role_access')->truncate();
        // Insert default role
        DB::table('role_access')->insert(
            array(
                //admin oper
                array('idrole'=>1,'idmenu'=>1),
                array('idrole'=>1,'idmenu'=>2),
                array('idrole'=>1,'idmenu'=>3),
                array('idrole'=>1,'idmenu'=>4),
                array('idrole'=>1,'idmenu'=>5),
                array('idrole'=>1,'idmenu'=>6),
                array('idrole'=>1,'idmenu'=>7),

                //admin vendor
                array('idrole'=>2,'idmenu'=>1),
                array('idrole'=>2,'idmenu'=>8),
                array('idrole'=>2,'idmenu'=>9),
                array('idrole'=>2,'idmenu'=>10),
                array('idrole'=>2,'idmenu'=>11),
                array('idrole'=>2,'idmenu'=>12),
                array('idrole'=>2,'idmenu'=>13),
                array('idrole'=>2,'idmenu'=>14),
                array('idrole'=>2,'idmenu'=>15),
                array('idrole'=>2,'idmenu'=>16),
                array('idrole'=>2,'idmenu'=>17),
                array('idrole'=>2,'idmenu'=>18),
                array('idrole'=>2,'idmenu'=>19),
                array('idrole'=>2,'idmenu'=>20),
                array('idrole'=>2,'idmenu'=>21),
                array('idrole'=>2,'idmenu'=>43),
                array('idrole'=>2,'idmenu'=>47),
                array('idrole'=>2,'idmenu'=>48),

                //admin client enterprise
                array('idrole'=>3,'idmenu'=>1),
                array('idrole'=>3,'idmenu'=>35),
                array('idrole'=>3,'idmenu'=>36),
                array('idrole'=>3,'idmenu'=>37),
                array('idrole'=>3,'idmenu'=>38),
                array('idrole'=>3,'idmenu'=>39),
                array('idrole'=>3,'idmenu'=>40),
                array('idrole'=>3,'idmenu'=>41),
                array('idrole'=>3,'idmenu'=>42),
                array('idrole'=>3,'idmenu'=>46),

                //dispatcher reg
                array('idrole'=>4,'idmenu'=>1),
                array('idrole'=>4,'idmenu'=>30),
                array('idrole'=>4,'idmenu'=>31),
                array('idrole'=>4,'idmenu'=>32),
                array('idrole'=>4,'idmenu'=>33),
                array('idrole'=>4,'idmenu'=>34),
                array('idrole'=>4,'idmenu'=>45),
                array('idrole'=>4,'idmenu'=>47),
                
                //dispatcher plus
                array('idrole'=>5,'idmenu'=>1),
                array('idrole'=>5,'idmenu'=>22),
                array('idrole'=>5,'idmenu'=>23),
                array('idrole'=>5,'idmenu'=>24),
                array('idrole'=>5,'idmenu'=>25),
                array('idrole'=>5,'idmenu'=>26),
                array('idrole'=>5,'idmenu'=>27),
                array('idrole'=>5,'idmenu'=>28),
                array('idrole'=>5,'idmenu'=>29),
                array('idrole'=>5,'idmenu'=>44),
                array('idrole'=>5,'idmenu'=>47),
            )
        );
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
