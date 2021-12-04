<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Models\ChangeEmail;
use App\Notifications\EmailActivation;
use App\Notifications\EmailActivationRequest;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use Carbon\Carbon;
use Notification;
use App\Http\Helpers\EventLog;

class ChangeEmailController extends Controller
{
    /**
     * Find request otp & phonenumber
     *
     * @param  [string] phonenumber
     * @param  [int] otp
     * @return [string] message
     * @return [json] changeEmail object
     */
    public function find($token)
    {
 
        $changeEmail = ChangeEmail::where('token', $token)
            ->first();

        if (!$changeEmail)
            throw new ApplicationException("change_email.invalid");

        if (Carbon::parse($changeEmail->updated_at)->addDays(Constant::CHANGE_EMAIL_LIFETIME)->isPast()) {
            $changeEmail->delete();
            throw new ApplicationException("change_email.expired");
        }
        
        if($users = User::where('email',$changeEmail->old_email)->firstOrFail()){ 
            $id = $users->id;           
            $users->email = $changeEmail->new_email;
            $users->status = Constant::STATUS_ACTIVE;
            $users->update();

            $users2 = User::where('id',$id)->first();
            return Response::success($users2);
        }else{            
            throw new ApplicationException("user.failure_save_user");
        }

    }
}
