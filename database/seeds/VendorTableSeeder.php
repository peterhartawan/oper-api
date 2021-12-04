<?php

use Illuminate\Database\Seeder;

class VendorTableSeeder extends Seeder
{

    public function run()
    {
        DB::table('vendor')->insert([
            "name" => "Mukti",
            "email" => "leni@indosystem.com",
            "office_phone_number" => "089123445678",
            "office_address" => "cirebon",
            "pic_name" => "seli",
            "pic_mobile_number" => "08976766543",
            "pic_email" => "seli.susanti@indosystem.com"
        ]);
    }
}

 