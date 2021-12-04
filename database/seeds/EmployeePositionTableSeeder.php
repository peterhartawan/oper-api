<?php
use Illuminate\Database\Seeder;

class EmployeePositionTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('employee_position')->insert([
            [
                "job_name" => "CEO",
            ],
        ]);
    }
}
