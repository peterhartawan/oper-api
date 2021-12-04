<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestOTP;
use App\Models\OrderTasks;
use App\Models\Inspector;
use App\Notifications\OTPSMSRequest;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use Carbon\Carbon;
use App\Http\Helpers\GlobalHelper;
use Illuminate\Support\Facades\Log;
use App\Http\Helpers\EventLog;

class RequestOTPController extends Controller
{

     /**
     * Create token password reset
     *
     * @param  [string] phonenumber
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'idordertask' => 'required|int',
            'phonenumber' => 'required|string'
        ]);

        $no_hp = GlobalHelper::replace_hp($request->phonenumber);
        
        //default 2 minutes
        $delay = Constant::OTP_DEFAULT_DELAY; //second
        
        // check if driver is the one that handle that order
        $orderTask = OrderTasks::with(['order' => function($query){
                            $query->where('driver_userid', auth()->guard('api')->user()->id);
                        }])
                        ->where('idordertask', $request->idordertask)
                        ->first();
        // dd($orderTask);
        // check if order is exist
        if(empty($orderTask->order)){
            throw new ApplicationException("otp.invalid_driver_idordertask", ['id' => $request->idordertask]);
        }

        // check is task need otp
        if ($orderTask->is_need_inspector_validation != true){
            throw new ApplicationException("otp.task_not_required_otp");
        }

        // check task status
        if ($orderTask->order_task_status != Constant::ORDER_TASK_INPROGRESS){
            throw new ApplicationException("otp.invalid_task_status");
        }
        // check if client id enterprise exist
        if(empty($orderTask->order->client_enterprise_identerprise)){
            throw new ApplicationException("otp.empty_client_enterprise");
        }

        // check if inspector phone number exist
        $inspector = Inspector::where("phonenumber", $no_hp);

        if ($inspector->count() < 1){
            throw new ApplicationException("otp.phone_not_found", ['phone' => $request->phonenumber]);
        }
        
        // check if inspector have same enterprise with client 
        $inspector = $inspector->where("client_enterprise_identerprise",$orderTask->order->client_enterprise_identerprise);
        if ($inspector->count() < 1)
            throw new ApplicationException("otp.inspector_not_registered", ['phone' => $request->phonenumber]);
        
        $checkOTP_request = RequestOTP::where('idordertask', $request->idordertask)
                            ->where('phonenumber', $no_hp)
                            ->orderby('idrequest_otp','desc')
                            ->first(); 
                            
        if( !empty($checkOTP_request) && $checkOTP_request->retry <= 3 ){
            if( $checkOTP_request->retry == 1 ){
                if($checkOTP_request->created_at->diffInSeconds() <= $delay){
                    throw new ApplicationException("otp.time_otp_validate");
                }
            }else{
                if($checkOTP_request->updated_at->diffInSeconds() <= $delay){
                    throw new ApplicationException("otp.time_otp_validate");
                }
            }
        }

        if( !empty($checkOTP_request) && $checkOTP_request->retry > 3 ){
            $delay = Constant::OTP_EXTEND_DELAY;

            if($checkOTP_request->updated_at->diffInSeconds() <= $delay){
                throw new ApplicationException("otp.time_otp_validate");
            }
        }
        $sms_from   = "OPER";
        $otp        = $this->generateOTP();
        $sms_msg    = "Your OTP Code is ".$otp; 

        //Check is staging or production?
        if (in_array(env('APP_ENV'), [Constant::ENV_STAGING, Constant::ENV_PRODUCTION])) {
            $sms    = $this->gw_send_sms($sms_from, $no_hp, $sms_msg);
        }
        else {
            $sms = true;
            $otp = '111111';
        }

        if($sms === true){

            $requestOTP = RequestOTP::updateOrCreate(
                [   
                    'phonenumber' => $no_hp,
                    'idordertask' => $request->idordertask
                ],
                [
                    'idordertask' => $request->idordertask,
                    'phonenumber' => $no_hp,
                    'otp' => $otp
                ]
            );

            $requestOTP->retry = ($requestOTP->retry + 1);
            $requestOTP->save();
            $response = new \stdClass();
            $response->delay = $delay;

            $dataraw = '';
            $reason  = 'Create or Update Request OTP #';
            $trxid   = $requestOTP->idrequest_otp;
            $model   = 'request otp';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($response, 'otp.sent');
        }else{
            Log::critical("failed send OTP to  : {$no_hp} ");
            throw new ApplicationException("otp.failed_send_otp");
        }       
    }

    /**
     * Find request otp & phonenumber
     *
     * @param  [string] phonenumber
     * @param  [int] otp
     * @return [string] message
     * @return [json] requestOTP object
     */
    public function check(Request $request)
    {

        $request->validate([
            'idordertask' => 'required|int',
            'phonenumber' => 'required|string',
            'otp' => 'required|int'
        ]);

        $requestOTP = RequestOTP::
              where('idordertask', $request->idordertask)
            ->where('phonenumber', $request->phonenumber)
            ->where('otp', $request->otp)
            ->first();
            
        if (!$requestOTP)
            throw new ApplicationException("otp.invalid");

        if (Carbon::parse($requestOTP->updated_at)->addMinutes(Constant::OTP_RESET_LIFETIME)->isPast()) {
            $requestOTP->delete();
            throw new ApplicationException("otp.expired");
        }
        return Response::success($requestOTP);
    }

    private function generateOTP($digits = 6){
        $i = 0; //counter
        $pin = ""; //our default pin is blank.
        while($i < $digits){
            //generate a random number between 0 and 9.
            $pin .= mt_rand(0, 9);
            $i++;
        }
        return $pin;
    }

    private function gw_send_sms($sms_from,$sms_to,$sms_msg){
        
        $query_string  = "api.aspx?apiusername=APIHL1VUYOI5F&apipassword=APIHL1VUYOI5F5WUGM";
        $query_string .= "&senderid=".rawurlencode($sms_from)."&mobileno=".rawurlencode($sms_to);
        $query_string .= "&message=".rawurlencode(stripslashes($sms_msg)) . "&languagetype=1";        
        $url           = "http://gateway.onewaysms.co.id:10002/".$query_string; 
        $fd            = @implode ('', file ($url));      
        if ($fd){                       
            if ($fd > 0) {
                //Print("MT ID : " . $fd);
                $ok = true;
            }        
            else {
                //print("Please refer to API on Error : " . $fd);
                $ok = false;
            }
        }else{                                           
                $ok = false;       
        }           
        return $ok; 
    }

   
}
