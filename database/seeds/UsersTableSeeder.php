<?php

use Illuminate\Database\Seeder;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        
        DB::table('users')->insert(
            array(
                array(
                    "name"=>"admin",
                    "email"=>"ipan.ardian@indosystem.com",
                    "phonenumber"=>"081271771",
                    "idrole"=>1,
                    "vendor_idvendor"=>NULL,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"admin vendor",
                    "email"=>"seli.susanti@indosystem.com",
                    "phonenumber"=>"08912345",
                    "idrole"=>2,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"client enterprise",
                    "email"=>"leni@indosystem.com",
                    "phonenumber"=>"089876543",
                    "idrole"=>3,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>1,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"dispatcher enterprise",
                    "email"=>"harisman.nugraha@indosystem.com",
                    "phonenumber"=>"089876543",
                    "idrole"=>4,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),               
                array(
                    "name"=>"dispatcher enterprise plus",
                    "email"=>"fuad.suyudi@indosystem.com",
                    "phonenumber"=>"089234567",
                    "idrole"=>5,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>1,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"dispatcher on demand",
                    "email"=>"cindy.lilian@indosystem.com",
                    "phonenumber"=>"089987654",
                    "idrole"=>6,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"Yaqub",
                    "email"=>"muhammad.yaqub@indosystem.com",
                    "phonenumber"=>"08912345667",
                    "idrole"=>7,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>1,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"Ismail",
                    "email"=>"ismail.baisa@indosystem.com",
                    "phonenumber"=>"08912345668",
                    "idrole"=>7,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>1,
                    "password"=>bcrypt("is2019")),
                
                //Regular
                array(
                    "name"=>"client enterprise reg",
                    "email"=>"ipan.ardian+enterpprisereg@indosystem.com",
                    "phonenumber"=>"089876543899",
                    "idrole"=>3,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>2,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"dispatcher enterprise reg",
                    "email"=>"ipan.ardian+dispatcherreg@indosystem.com",
                    "phonenumber"=>"0898765437",
                    "idrole"=>4,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"driver1 reg",
                    "email"=>"ipan.ardian+driver1@indosystem.com",
                    "phonenumber"=>"0898765437",
                    "idrole"=>7,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>2,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"driver2 reg",
                    "email"=>"ipan.ardian+driver2@indosystem.com",
                    "phonenumber"=>"0898765437",
                    "idrole"=>7,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>2,
                    "password"=>bcrypt("is2019")),
                array(
                    "name"=>"employee",
                    "email"=>"ipan.ardian+employee@indosystem.com",
                    "phonenumber"=>"0898765437",
                    "idrole"=>8,
                    "vendor_idvendor"=>1,
                    "client_enterprise_identerprise"=>NULL,
                    "password"=>bcrypt("is2019")),
            )           
        );
    }
}
