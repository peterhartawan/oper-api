<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RoleTableSeeder::class,
            VendorTableSeeder::class,
            EnterpriseTypeTableSeeder::class,
            OrderTypeTableSeeder::class,
            DriverTypeSeeder::class,
            ClientEnterpriseTableSeeder::class,
            UsersTableSeeder::class,
            WebMenuTableSeeder::class,
            RoleAccessTableSeeder::class,
            DriverTableSeeder::class,
            EmployeePositionTableSeeder::class,
            EmployeeTableSeeder::class,
        ]);
    }
}
