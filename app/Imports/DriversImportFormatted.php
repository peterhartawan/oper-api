<?php

namespace App\Imports;

use App\User;
use App\Models\Driver;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use App\Constants\Constant;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\Response;

class DriversImportFormatted implements ToCollection, WithChunkReading
{
    private $request;
    private $result;

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

            $fullname       = trim($row[0]);
            $email          = trim($row[1]);
            $phone          = preg_replace('/\s/', '', $row[2]);
            $birthdate      = !empty($row[3]) ? trim($row[3]) : NULL;
            $address        = !empty($row[4]) ? trim($row[4]) : NULL;
            $nik            = !empty($row[5]) ? trim($row[5]) : NULL;
            $gender         = strtolower(trim($row[6]));
            $gender         = $gender == 'male' ? Constant::GENDER_MALE : Constant::GENDER_FEMALE;
            $pass           = 'oper2019';
            $idvendor       = $this->request->idvendor;

            if (empty($email) || empty($fullname))
                continue;

            DB::beginTransaction();
            try {

                $user = User::create([
                    'name'  => $fullname,
                    'email' => $email,
                    'password'  => bcrypt($pass),
                    'phonenumber'   => $phone,
                    'idrole'    => Constant::ROLE_DRIVER,
                    'vendor_idvendor'   => $idvendor,
                    'profile_picture'  => NULL,
                    'status'    => constant::STATUS_ACTIVE
                ]);
                
                $driver = Driver::create([
                    'users_id' => $user->id,
                    'birthdate' => $birthdate,
                    'address' => $address,
                    'drivertype_iddrivertype' => Constant::DRIVER_TYPE_PKWT_BACKUP,
                    // 'dispatcher_vendor_idvendor' => $idvendor,
                    'nik' => $nik,
                    'gender' => Constant::GENDER_MALE,
                    'created_by'=> 1
                ]);
                
                if ($user && $driver)
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
