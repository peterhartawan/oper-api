<?php

use Illuminate\Database\Seeder;

class WebMenuTableSeeder extends Seeder
{
  
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // DB::table('web_menu')->truncate();
        // // Insert web menu
        DB::table('web_menu')->insert([
            [
                'idmenu'=>1,
                'name'=>'Home',
                'slug'=> '/dashboard',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-chart-bar',
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ],

            // For Admin Oper
            [
                'idmenu'=>2,
                'name'=>'Order',
                'slug'=> '/order/open',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-chart-bar',
                'sequence' => '2',
                'static_content_idstatic_content' => null
            ],           
            [
                // Vendor Account
                'idmenu'=>3,
                'name'=>'Manage Vendor',
                'slug'=> '/vendor',
                'parent_idmenu' => null,
                'icon' => null,
                'sequence' => '6',
                'static_content_idstatic_content' => null
            ],         
            [
                 // Enterprise Account
                'idmenu'=>4,
                'name'=>'Manage Enterprise',
                'slug'=> '/enterprise',
                'parent_idmenu' => null,
                'icon' => null,
                'sequence' => '7',
                'static_content_idstatic_content' => null
            ],                
            [
                'idmenu'=>5,
                'name'=>'Manage Client-on-demand',
                'slug'=> '/client-on-demand',
                'parent_idmenu' => null,
                'icon' => null,
                'sequence' => '8',
                'static_content_idstatic_content' => null
            ],                
            [
                'idmenu'=>6,
                'name'=>'Manage FAQ',
                'slug'=> '/manage-faq',
                'parent_idmenu' => null,
                'icon' => null,
                'sequence' => '9',
                'static_content_idstatic_content' => null
            ],                
            [
                'idmenu'=>7,
                'name'=>'Manage Static Content',
                'slug'=> '/pages',
                'parent_idmenu' => null,
                'icon' => null,
                'sequence' => '10',
                'static_content_idstatic_content' => null
            ],                
            //End For Admin Oper

            //For Vendor
            [
                // Dispatcher Account
                'idmenu'=>8,
                'name'=>'Account',
                'slug'=> '/dispatcher',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-account-multiple',
                'sequence' => '2',
                'static_content_idstatic_content' => null
            ],
            [
                // Enterprise Account
                'idmenu'=>9,
                'name'=>'Partner',
                'slug'=> '/enterprise',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-domain',
                'sequence' => '3',
                'static_content_idstatic_content' => null
            ],
            [
                // Driver             
                'idmenu'=>10,
                'name'=>'Driver',
                'slug'=> '',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-account-multiple',
                'sequence' => '4',
                'static_content_idstatic_content' => null
            ],
            [
                // Driver account            
                'idmenu'=>11,
                'name'=>'Account',
                'slug'=> '/driver',
                'parent_idmenu' => 10,
                'icon' => null,
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ],
            [
                // Driver attendance
                'idmenu'=>12,
                'name'=>'Attendance',
                'slug'=> '/attendance/driver',
                'parent_idmenu' => 10,
                'icon' => null,
                'sequence' => '2',
                'static_content_idstatic_content' => null
            ], 
            [
                // Driver order
                'idmenu'=>13,
                'name'=>'Order',
                'slug'=> '/order/open',
                'parent_idmenu' => 10,
                'icon' => null,
                'sequence' => '3',
                'static_content_idstatic_content' => null
            ],
            [
                // Employee
                'idmenu'=>14,
                'name'=>'Employee',
                'slug'=> '',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-account-multiple',
                'sequence' => '5',
                'static_content_idstatic_content' => null
            ],
            [
                // Employee account            
                'idmenu'=>15,
                'name'=>'Account',
                'slug'=> '/employee',
                'parent_idmenu' => 14,
                'icon' => null,
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ],
            [
                // Employee attendance
                'idmenu'=>16,
                'name'=>'Attendance',
                'slug'=> '/attendance/employee',
                'parent_idmenu' => 14,
                'icon' => null,
                'sequence' => '2',
                'static_content_idstatic_content' => null
            ], 
            [
                // Employee order
                'idmenu'=>17,
                'name'=>'Task',
                'slug'=> '/employee/inprogress',
                'parent_idmenu' => 14,
                'icon' => null,
                'sequence' => '3',
                'static_content_idstatic_content' => null
            ], 
            [
                // Help Center  
                'idmenu'=>18,
                'name'=>'Help Center',
                'slug'=> null,
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '6',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>19,
                'name'=>'FAQ',
                'slug'=> '/faq',
                'parent_idmenu' => 18,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>20,
                'name'=>'Privacy Policy',
                'slug'=> '/pages/privacy',
                'parent_idmenu' => 18,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '2',
                'static_content_idstatic_content' => '1'
            ], 
            [
                'idmenu'=>21,
                'name'=>'Terms and Condition',
                'slug'=> '/pages/terms-and-condition',
                'parent_idmenu' => 18,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '3',
                'static_content_idstatic_content' => '2'
            ],
            // End for Vendor

            //For  Dispatcher plus
            [
                'idmenu'=>22,
                'name'=>'Create Order',
                'slug'=> '',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'2',
                'static_content_idstatic_content' => null
            ],
            [
                'idmenu'=>23,
                'name'=>'Create Order',
                'slug'=> '/order/create',
                'parent_idmenu' => 22,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'4',
                'static_content_idstatic_content' => null
            ],
            [
                'idmenu'=>24,
                'name'=>'Bulk Order',
                'slug'=> '/order/bulk',
                'parent_idmenu' => 22,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'5',
                'static_content_idstatic_content' => null  
            ],
            [
                'idmenu'=>25,
                'name'=>'Order',
                'slug'=> '/order/open',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'3',
                'static_content_idstatic_content' => null
            ],
            [
                // Help Center  
                'idmenu'=>26,
                'name'=>'Help Center',
                'slug'=> null,
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '6',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>27,
                'name'=>'FAQ',
                'slug'=> '/faq',
                'parent_idmenu' => 26,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>28,
                'name'=>'Privacy Policy',
                'slug'=> '/pages/privacy',
                'parent_idmenu' => 26,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '2',
                'static_content_idstatic_content' => '1'
            ], 
            [
                'idmenu'=>29,
                'name'=>'Terms and Condition',
                'slug'=> '/pages/terms-and-condition',
                'parent_idmenu' => 26,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '3',
                'static_content_idstatic_content' => '2'
            ],
            //end for dispatcher plus

            //For  Dispatcher reg
            [
                'idmenu'=>30,
                'name'=>'Order',
                'slug'=> '/order/open',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'3',
                'static_content_idstatic_content' => null
            ],
            [
                // Help Center  
                'idmenu'=>31,
                'name'=>'Help Center',
                'slug'=> null,
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '6',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>32,
                'name'=>'FAQ',
                'slug'=> '/faq',
                'parent_idmenu' => 31,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '1',
                'static_content_idstatic_content' => null
            ], 
            [
                'idmenu'=>33,
                'name'=>'Privacy Policy',
                'slug'=> '/pages/privacy',
                'parent_idmenu' => 31,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '2',
                'static_content_idstatic_content' => '1'
            ], 
            [
                'idmenu'=>34,
                'name'=>'Terms and Condition',
                'slug'=> '/pages/terms-and-condition',
                'parent_idmenu' => 31,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '3',
                'static_content_idstatic_content' => '2'
            ],
            //end for dispatcher reg

            //For Client Enterprise
            [
                'idmenu'=>35,
                'name'=>'Create Order',
                'slug'=> '',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'2',
                'static_content_idstatic_content' => null  
            ],
            [
                'idmenu'=>36,
                'name'=>'Create Order',
                'slug'=> '/order/create',
                'parent_idmenu' => 35,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'4',
                'static_content_idstatic_content' => null 
            ],
            [
                'idmenu'=>37,
                'name'=>'Bulk Order',
                'slug'=> '/order/bulk',
                'parent_idmenu' => 35,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'5',
                'static_content_idstatic_content' => null  
            ],
            [
                'idmenu'=>38,
                'name'=>'Order',
                'slug'=> '/order/open',
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-taxi',
                'sequence' =>'3',
                'static_content_idstatic_content' => null
            ],
            [            
                'idmenu'=>39,
                'name'=>'Help Center',
                'slug'=> null,
                'parent_idmenu' => null,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '9',
                'static_content_idstatic_content' => null
            ], 
            [           
                'idmenu'=>40,
                'name'=>'FAQ',
                'slug'=> '/faq',
                'parent_idmenu' => 39,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '10',
                'static_content_idstatic_content' => null
            ], 
            [            
                'idmenu'=> 41,
                'name'=>'Privacy Policy',
                'slug'=> '/pages/privacy',
                'parent_idmenu' => 39,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '11',
                'static_content_idstatic_content' => '4',
            ], 
            [           
                'idmenu'=>42,
                'name'=>'Terms and Condition',
                'slug'=> '/pages/terms-and-condition',
                'parent_idmenu' => 39,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '12',
                'static_content_idstatic_content' => '5'
            ], 
            [           
                'idmenu'=>43,
                'name'=>'Customer Service',
                'slug'=> '/pages/customer-service',
                'parent_idmenu' => 18,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '4',
                'static_content_idstatic_content' => '3'
            ],
            [ 
                'idmenu'=>44,
                'name'=>'Customer Service',
                'slug'=> '/pages/customer-service',
                'parent_idmenu' => 26,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '4',
                'static_content_idstatic_content' => '3'
            ],
            [
                'idmenu'=>45,
                'name'=>'Customer Service',
                'slug'=> '/pages/customer-service',
                'parent_idmenu' => 31,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '4',
                'static_content_idstatic_content' => '3'
            ],
            [
                'idmenu'=>46,
                'name'=>'Customer Service',
                'slug'=> '/pages/customer-service',
                'parent_idmenu' => 39,
                'icon' => 'mdi mdi-file-document',
                'sequence' => '13',
                'static_content_idstatic_content' => '6'
            ],
            [
                'idmenu'=>47,
                'name'=>'Reporting',
                'slug'=> '/order/reporting',
                'parent_idmenu' => 10,
                'icon' => '',
                'sequence' => '4',
                'static_content_idstatic_content' => null
            ],
            [
                'idmenu'=>48,
                'name'=>'Reporting',
                'slug'=> '/employee/reporting',
                'parent_idmenu' => 14,
                'icon' => '',
                'sequence' => '4',
                'static_content_idstatic_content' => null
            ],
            // End Client enterprise
        ]);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
