<?php

use Illuminate\Database\Seeder;

class ClientEnterpriseTableSeeder extends Seeder
{

    public function run()
    {
        DB::table('client_enterprise')->insert([
            [
                "name" => "BCA",
                "email" => "iyohmbul@gmail.com",
                "description" => "",
                "enterprise_type_identerprise_type" => 2,
                "vendor_idvendor" => 1,                
                "office_phone" => "0891234567",
                "office_address" => "cirebon",
                "pic_name" => "leni" ,
                "pic_phone" => "08934567890" ,
                "pic_email" => "leni@indosystem.com",
                "site_url" => "http://oper-customer.festiware.com"         
            ],
            [
                "name" => "BMG",
                "email" => "asrini@indosystem.com",
                "description" => "",
                "enterprise_type_identerprise_type" => 1,
                "vendor_idvendor" => 1,                
                "office_phone" => "0987654432",
                "office_address" => "cirebon",
                "pic_name" => "seli" ,
                "pic_phone" => "0896534677" ,
                "pic_email" => "seli.susanti+1@indosystem.com",
                "site_url" => "http://oper-customer.festiware.com"      
            ]
    
        ]);
    }
}

 