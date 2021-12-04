<?php

use Illuminate\Database\Seeder;

class EnterpriseTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('enterprise_type')->insert([
            ["identerprise_type"=>1,"name"=>"enterprise regular"],
            ["identerprise_type"=>2,"name"=>"enterprise plus"],
            ["identerprise_type"=>3,"name"=>"enterprise on-demand"]
        ]);
    }
}
