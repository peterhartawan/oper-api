<?php

use Illuminate\Database\Seeder;

class DispatcherTableSeeder extends Seeder
{

    public function run()
    {
        DB::table('dispatcher')->insert([
            [
                "users_id" => 4,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                "gender" => "1",
                "status" => "1"
            ],
            [
                "users_id" => 5,
                "birthdate" => "1996-08-22",
                "address" => "cirebon",
                "gender" => "1",
                "status" => "1"
            ],
            [
                "users_id" => 9,
                "birthdate" => "1996-08-22",
                "address" => "cirebon",
                "gender" => "1",
                "status" => "1"
            ]
        ]);
    }
}

 