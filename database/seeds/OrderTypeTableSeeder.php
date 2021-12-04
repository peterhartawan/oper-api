<?php

use Illuminate\Database\Seeder;

class OrderTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('order_type')->insert([
            ["idorder_type"=>1,"name"=>"enterprise"],
            ["idorder_type"=>2,"name"=>"enterprise plus"],
            ["idorder_type"=>3,"name"=>"enterprise on-demand"],
            ["idorder_type"=>4,"name"=>"enterprise private"],
            ["idorder_type"=>5,"name"=>"on-demand"],
            ["idorder_type"=>6,"name"=>"employee"]
        ]);
    }
}
