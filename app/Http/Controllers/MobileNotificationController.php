<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Services\Validate;
use App\Constants\Constant;
use App\Notifications\AccountActivation;
use DB;
use App\User;
use App\PasswordReset;
use App\Models\MobileNotification;
use App\Exceptions\ApplicationException;

class MobileNotificationController extends Controller
{
    
    public function mobilenotification(Request $request)
    {

        Validate::request($request->all(), [
            'device_id'   => 'required|string',
            'token'       => 'required|string',
            'device_type' => 'nullable|string',
            'device_info' => 'nullable|string',
        ]);
        
        $user_id            = auth()->guard('api')->user()->id;
        
        $mobilenotification = MobileNotification::updateOrCreate(
            ['user_id' => $user_id],
            [
                'user_id'       => $user_id,
                'device_id'     => $request->device_id,
                'token'         => $request->token,
                'device_type'   => $request->device_type,
                'device_info'   => $request->device_info
            ]
        );

        return $mobilenotification;
    }

}