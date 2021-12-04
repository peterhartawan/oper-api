<?php

use Illuminate\Database\Seeder;

class DriverTableSeeder extends Seeder
{

    public function run()
    {
        DB::table('driver')->insert([
            [
                "users_id" => 7,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                "drivertype_iddrivertype" => 1,
            ],
            [
                "users_id" => 8,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                "drivertype_iddrivertype" => 1,
            ],
            [
                "users_id" => 10,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                "drivertype_iddrivertype" => 1,
            ],
            [
                "users_id" => 11,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                "drivertype_iddrivertype" => 1,
            ],
        ]);
    }
}
