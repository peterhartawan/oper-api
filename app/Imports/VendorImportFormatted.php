<?php

namespace App\Imports;

use App\User;
use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Constants\Constant;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\Response;

class VendorImportFormatted implements ToCollection, WithChunkReading
{
    function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function collection(Collection $rows)
    {
        $i = 0;
        $success = 0;
        $failure = 0;

        foreach ($rows as $row) 
        {
            $i++;
            if ($i < 2)
                continue;

            $name                   = trim($row[0]);
            $email                  = trim($row[1]);
            $office_phone_number    = preg_replace('/\s/', '', $row[2]);
            $office_address         = !empty($row[3]) ? trim($row[3]) : NULL;
            $pic_name               = !empty($row[4]) ? trim($row[4]) : NULL;
            $pic_mobile_number      = !empty($row[5]) ? trim($row[5]) : NULL;
            $pic_email              = trim($row[6]);
            $admin_name             = trim($row[7]);
            $admin_mobile_number    = trim($row[8]);
            $admin_email            = trim($row[9]);
            $pass                   = 'oper2019';

            if (empty($email) || empty($name))
                continue;

            DB::beginTransaction();
            try {
                $vendor = Vendor::create([
                    'name' => $name,
                    'email' => $email,
                    'office_phone_number' => $office_phone_number,
                    'office_address' => $office_address,
                    'pic_name' => $pic_name,
                    'pic_mobile_number' => $pic_mobile_number,
                    'pic_email' => $pic_email,
                    'created_by'=> 1
                ]);

                $user = User::create([
                    'name'  => $admin_name,
                    'email' => $admin_email,
                    'password'  => bcrypt($pass),
                    'phonenumber'   => $admin_mobile_number,
                    'idrole'    => Constant::ROLE_VENDOR,
                    'vendor_idvendor'   => $vendor->idvendor,
                    'profile_picture'  => NULL,
                    'status'    => constant::STATUS_ACTIVE
                ]);
                
              
                
                if ($user && $vendor)
                    $success++;

                DB::commit();
            } catch (\Exception $ex) {
                $failure++;
                DB::rollBack();
                Log::error($ex->getMessage());
            }
        }

        Log::info('Success: ' . $success . ' Fail: ' . $failure);

        $this->result = [
            'success'   => $success,
            'fail'      => $failure
        ];
    }

    public function chunkSize(): int
    {
        return 300;
    }

    public function getResult()
    {
        return $this->result;
    }
}
