<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use App\Models\B2C\OTPB2C;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Exceptions\ApplicationException;
use App\Services\QontakHandler;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OTPB2CController extends Controller
{
    public function store(Request $request){
        Validate::request($request->all(), [
            'phone' => 'required'
        ]);

        // Generate OTP
        $code = "";

        for($i = 0; $i< 4; $i++){
            $code.= rand(0, 9);
        }

        $otp_data = [
            'phone' => $request->phone,
            'code'  => $code
        ];

        DB::beginTransaction();

        try{
            //create new rating
            $otp = OTPB2C::create($otp_data);

            // BLAST
            $qontakHandler = new QontakHandler();

            $response = $qontakHandler->sendMessage(
                "62".$otp->phone,
                "OTP",
                Constant::QONTAK_TEMPLATE_ID_OTP,
                [
                    [
                        "key"=> "1",
                        "value"=> "otp",
                        "value_text"=> $otp->code
                    ]
                ]
            );

            return Response::success($response);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("otp.failed_send_otp");
        }
    }

    public function verify(Request $request){
        Validate::request($request->all(), [
            'phone' => 'required',
            'code' => 'required'
        ]);

        DB::beginTransaction();

        try{
            //Get OTP
            $otp = OTPB2C::latest('created_at')
                ->where('phone', $request->phone)
                ->where('code', $request->code)
                ->first();

            //OTP found
            if(!empty($otp)){
                $now = Carbon::now()->format('Y-m-d H:i:s');
                $then = Carbon::parse($otp->created_at)->addMinutes(1)->format('Y-m-d H:i:s');

                //Is not expired
                if($now <= $then){
                    // Update otp status
                    OTPB2C::latest('created_at')
                        ->where('phone', $request->phone)
                        ->where('code', $request->code)
                        ->update(['status' => 1]);

                    return Response::success($otp->phone);
                }
                else
                    throw new ApplicationException("otp.expired");
            }
            else
                throw new ApplicationException("otp.invalid");
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("otp.failed_send_otp");
        }
    }
}
