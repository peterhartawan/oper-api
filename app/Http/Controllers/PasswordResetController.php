<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Notifications\PasswordResetRequest;
use App\Notifications\PasswordResetSuccess;
use App\Notifications\CreateNewPasswordSuccess;
use App\Notifications\UserNotification;
use App\Notifications\NewClientForVendor;
use App\User;
use App\PasswordReset;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use App\Models\ClientEnterprise;
use App\Models\Vendor;
use App\Models\Dispatcher;
use App\Models\Inspector;
use App\Http\Helpers\EventLog;
use DB;

class PasswordResetController extends Controller
{

    /**
     * Create token password reset
     *
     * @param  [string] email
     * @return [string] message
     */
    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|max:100|email'
        ]);

        $user             = User::where('email', $request->email)->first();
        $role_update      = $user->idrole ;

        if( $role_update == Constant::ROLE_SUPERADMIN ){
            $linkurl = env('URL_ADMIN_OPER');
        } 
        elseif( $role_update == Constant::ROLE_VENDOR || $role_update == Constant::ROLE_DRIVER || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS 
        || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $role_update == Constant::ROLE_DISPATCHER_ONDEMAND || $role_update == Constant::ROLE_EMPLOYEE){
            $linkurl = env('URL_VENDOR');
        }
        elseif( $role_update == Constant::ROLE_ENTERPRISE  ){
            $ClientEnterprises = ClientEnterprise::where('identerprise',$user->client_enterprise_identerprise)->first();
            $linkurl = $ClientEnterprises->site_url;
        }

        if (!$user)
            throw new ApplicationException("user.user_not_found", ['email' => $request->email]);
            
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );
        if ($user && $passwordReset)
            $user->notify(
                new PasswordResetRequest($passwordReset->token,$linkurl)
            );
        
        return Response::success(['email' => $user->email], 
            'user.reset_password_request');
    }

    /**
     * Find token password reset
     *
     * @param  [string] $token
     * @return [string] message
     * @return [json] passwordReset object
     */
    public function find($token)
    {
        $passwordReset = PasswordReset::where('token', $token)
                         ->first();

        if (!$passwordReset)
            throw new ApplicationException("user.token_invalid");
            
        if (Carbon::parse($passwordReset->updated_at)->addDays(Constant::TOKEN_ACTIVATION_LIFETIME)->isPast()) {
            
            $user       = User::where('email', $passwordReset->email)->first();
            if (!$user)
                throw new ApplicationException("user.user_not_found", ['email' => $passwordReset->email]);

            switch ($user->idrole) {

                case Constant::ROLE_VENDOR:
                    $vendors = Vendor::where('idvendor',$user->vendor_idvendor)->first(); 
                    $user->delete();
                    $vendors->delete(); 
                    $passwordReset->delete();
                break;

                case Constant::ROLE_ENTERPRISE:
                    $enterprise = ClientEnterprise::where('identerprise',$user->client_enterprise_identerprise)->first(); 
                    $inspektor  = Inspector::where('client_enterprise_identerprise',$user->client_enterprise_identerprise)->first();
                    
                    $inspektor->delete(); 
                    $user->delete();  
                    $enterprise->delete(); 
                    $passwordReset->delete();

                break;

                case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                case Constant::ROLE_DISPATCHER_ONDEMAND:
                    $dispatcher = Dispatcher::where('users_id',$user->id)->first();
                    $user->delete();
                    $dispatcher->delete();
                    $passwordReset->delete();
                break;

            }
            throw new ApplicationException("user.token_expired");
        }
        return Response::success($passwordReset);
    }

     /**
     * Reset password
     *
     * @param  [string] email
     * @param  [string] password
     * @param  [string] password_confirmation
     * @param  [string] token
     * @return [string] message
     * @return [json] user object
     */
    public function reset(Request $request)
    {
        $request->validate([
            'password' => 'required|string|confirmed|between:8,16',
            'token' => 'required|string'
        ]);
        $passwordReset = PasswordReset::where([
                            ['token', $request->token]
                        ])->first();

        if (!$passwordReset)
            throw new ApplicationException("user.token_invalid");

        $user = User::where('email', $passwordReset->email)->first();
        if (!$user)
            throw new ApplicationException("user.user_not_found", ['email' => $request->email]);

        $user->password = bcrypt($request->password);

        // change status to active if user is inactive before
        if ($user->status == constant::STATUS_INACTIVE){
            $user->status = constant::STATUS_ACTIVE;

            //jika user admin vendor
            if ($user->idrole == constant::ROLE_VENDOR) {
                $vendors = Vendor::where('idvendor',$user->vendor_idvendor)
                        ->update([
                                'status' => constant::STATUS_ACTIVE
                            ]);         
            }

            //jika user admin client
            if ($user->idrole == constant::ROLE_ENTERPRISE) {
                $ClientEnterprise = ClientEnterprise::where('identerprise',$user->client_enterprise_identerprise)
                        ->update(['status' => constant::STATUS_ACTIVE]);
            }

            // if user is newly activated enterprise send notification to vendor
            if($user->idrole == constant::ROLE_ENTERPRISE && $user->vendor_idvendor != null){

                $emails = User::where('vendor_idvendor', $user->vendor_idvendor)
                        ->where('idrole', constant::ROLE_VENDOR)
                        ->first();

                if($emails){
                    $emails->notify(
                        new NewClientForVendor($user->id,$user->name)
                    );
                }

                $adminVendors =  User::where("vendor_idvendor",$user->vendor_idvendor)
                ->Where("idrole",constant::ROLE_VENDOR)
                ->Where("status",constant::STATUS_ACTIVE)
                ->get();

                if (!$adminVendors) 
                throw new ApplicationException("vendors.vendor_not_found", ['email' => $request->email]);

                foreach ($adminVendors as $adminVendor) {
                    $adminVendor->notify(
                        new UserNotification("{$user->email} registered as your user")
                    );
                }

            }


            $user->save();
            $passwordReset->delete();
            $user->notify(new CreateNewPasswordSuccess($passwordReset));
            
        }else{
            $user->save();
            $passwordReset->delete();
            $user->notify(new PasswordResetSuccess($passwordReset));
        }

        return Response::success($user);
    }

}