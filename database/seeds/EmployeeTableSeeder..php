<?php

use Illuminate\Database\Seeder;

class EmployeeTableSeeder extends Seeder
{

    public function run()
    {
        DB::table('employee')->insert([
            [
                "users_id" => 12,
                "birthdate" => "1996-08-21",
                "address" => "cirebon",
                'idemployee_position' => 1
            ],
        ]);
    }
}

 