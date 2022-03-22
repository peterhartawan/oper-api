<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Response;
use App\Services\Validate;
use App\Notifications\AccountActivation;
use App\User;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Order;
use App\PasswordReset;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use App\Models\ChangeEmail;
use App\Notifications\EmailActivation;
use App\Notifications\EmailActivationRequest;
use App\Models\ClientEnterprise;
use Illuminate\Support\Facades\Storage;
use App\Notifications\UserNotification;
use App\Http\Helpers\EventLog;

class UserController extends Controller
{

    /**
     * Create user
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] phonenumber
     * @param  [int]    idrole from role
     * @param  [string] password_confirmation
     * @return [string] message
     */
    public function store(Request $request)
    {
		Validate::request($request->all(), [
            'name' => 'required|string',
            'email' => 'required|max:100|email|unique:users',
            'phonenumber' => 'required|string|unique:users',
        ]);

        // Only Admin OPER is allowed to create admin oper account
        $role_user_login = auth()->guard('api')->user()->idrole;
        if ($role_user_login != constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        DB::beginTransaction();
        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt(str_random(12)),
                'phonenumber' => $request->phonenumber,
                'idrole' => constant::ROLE_SUPERADMIN,
                'vendor_idvendor' => $request->id_vendor,
                'client_enterprise_identerprise' => $request->id_enterprise,
                'status' => constant::STATUS_INACTIVE
            ]);

            // create password reset request
            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => str_random(60)
                ]
            );

            $array = array(
                'user_id' => $user->id
            );

            $dataraw = json_encode($array);
            $reason  = 'Create user email ';
            $trxid   = $user->email;
            $model   = 'user';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();

            $linkurl = env('URL_ADMIN_OPER');
            if ($user && $passwordReset) {
                $user->notify(
                    new AccountActivation($passwordReset->token, $linkurl)
                );
            }

            return Response::success($user);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("user.failure_save_user");
        }
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function index()
    {
        return Response::success(auth()->guard('api')->user());
    }

    /**
     * Get the authenticated User detail
     *
     * @return [json] user object
     */
    public function me()
    {
        $role_login = auth()->guard('api')->user()->idrole;
        $userid     = auth()->guard('api')->user()->id;

        if(empty($userid)){
            throw new ApplicationException("user.failure_save_user");
        }

        $dispatcherRole = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];
        $driverRole = [
            Constant::ROLE_DRIVER
        ];
        $employeeRole = [
            Constant::ROLE_EMPLOYEE
        ];


        if (in_array($role_login, $dispatcherRole)) {
            //query dispatcher
            $detail_user = User::select(DB::raw("CONCAT('Member Since,',' ',DATE_FORMAT(users.created_at, '%d %M %Y')) as join_date"),"users.*")
                    ->where('id', $userid)
                    ->with(["role","vendor","enterprise","driver_profile","dispatcher_profile"])
                    ->first();
        }
        elseif (in_array($role_login, $driverRole)) {
            //query driveer
            $detail_user = User::select(DB::raw("CONCAT('Member Since,',' ',DATE_FORMAT(users.created_at, '%d %M %Y')) as join_date"),"users.*")
                    ->where('id', $userid)
                    ->with(["role","vendor","enterprise",
                        "driver_profile" => function($query) {
                            $query->selectRaw(
                                    "driver.*, CONCAT('Jam: ', DATE_FORMAT(stay_time, '%H.%i'), ' WIB') as stay_time"
                                )
                                ->with(["places" => function($query){
                                    $query->selectRaw(
                                        "idplaces, CONCAT('Lokasi Stay: ' , name) as name"
                                    );
                                }]);
                        },
                        "dispatcher_profile"])
                    ->first();

            $driver = Driver::where("users_id", $userid)
                ->leftJoin('users','driver.users_id','=','users.id')
                ->join('order','order.driver_userid','=','users.id')
                ->where('order.order_status',Constant::ORDER_INPROGRESS)
                ->get();

            if(count($driver) > 0){
                $detail_user->driver_profile->is_on_order = Constant::BOOLEAN_TRUE;
            }else{
                $detail_user->driver_profile->is_on_order = Constant::BOOLEAN_FALSE;
            }

        }
        elseif (in_array($role_login, $employeeRole)) {
            //query employee
            $detail_user = User::select(DB::raw("CONCAT('Member Since,',' ',DATE_FORMAT(users.created_at, '%d %M %Y')) as join_date"),"users.*")
                    ->where('id', $userid)
                    ->with(["role","vendor","employee_profile"])
                    ->first();

            $employee = Employee::where("users_id", $userid)
                ->leftJoin('users', 'employee.users_id', '=', 'users.id')
                ->join('order','order.employee_userid','=','users.id')
                ->where('order.order_status',Constant::ORDER_INPROGRESS)
                ->get();

            if(count($employee) > 0){
                $detail_user->employee_profile->is_on_task = Constant::BOOLEAN_TRUE;
            }else{
                $detail_user->employee_profile->is_on_task = Constant::BOOLEAN_FALSE;
            }

        }
        else {
            $cek_role = User::where('id', auth()->guard('api')->user()->id)
                        ->with(["role", "vendor", "enterprise"])
                        ->first();

            if($cek_role->idrole ==Constant::ROLE_ENTERPRISE){
                if ($cek_role->enterprise->enterprise_type->identerprise_type == Constant::ENTERPRISE_TYPE_PLUS) {

                    $detail_user = User::where('id', auth()->guard('api')->user()->id)
                                ->with(["role", "vendor", "enterprise", "dispatcher"])
                                ->first();

                }else{

                    $detail_user = User::where('id', auth()->guard('api')->user()->id)
                                ->with(["role", "vendor", "enterprise"])
                                ->first();

                }
            }else{
                $detail_user = User::where('id', auth()->guard('api')->user()->id)
                            ->with(["role", "vendor", "enterprise", "dispatcher_profile"])
                            ->first();
            }


        }
         // change image url to laravel path
        if (!empty($detail_user->profile_picture)) {
            $detail_user->profile_picture = Storage::url($detail_user->profile_picture);
            $detail_user->profile_picture = env('BASE_API').$detail_user->profile_picture;
        }


        return Response::success($detail_user);

    }

    /**
     * Get User by id
     *
     * @return [json] user object
     */
    public function show($id)
    {
        if($user = User::where('id', $id)->first()){
            return Response::success($user);
        }else{
            throw new ApplicationException("user.failure_save_user");
        }
    }

    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'name'        => 'required|string',
            'phonenumber' => 'required|string|unique:users,phonenumber,'.$id,
            'email'       => 'required|max:100|email|unique:users,email,'.$id,
        ]);

        $users            = User::findOrFail($id);
        $status           = Constant::OPTION_DISABLE;
        $role_login       = auth()->guard('api')->user()->idrole ;
        $role_update      = $users->idrole ;
        $new_email        = $request->email ;

        $dispatcherRole = [
            Constant::ROLE_ENTERPRISE,
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
            ];

        if( $role_update == Constant::ROLE_SUPERADMIN ){
            $linkurl = env('URL_ADMIN_OPER');
        }
        elseif( $role_update == Constant::ROLE_VENDOR || $role_update == Constant::ROLE_DRIVER || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS
        || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $role_update == Constant::ROLE_DISPATCHER_ONDEMAND ){
            $linkurl = env('URL_VENDOR');
        }elseif( $role_update == Constant::ROLE_ENTERPRISE  ){
            $ClientEnterprises = ClientEnterprise::where('identerprise',$users->client_enterprise_identerprise)->first();
            $linkurl = $ClientEnterprises->site_url;
        }


        if($users){

            if($role_login == Constant::ROLE_SUPERADMIN){
                    $status   = Constant::OPTION_ENABLE;
            }elseif($role_login == Constant::ROLE_VENDOR){
                if($role_update == Constant::ROLE_DRIVER){
                    $status   = Constant::OPTION_ENABLE;
                }
                if(in_array($role_update, $dispatcherRole)){
                    $status     = Constant::OPTION_ENABLE;
                }
                if($role_update == Constant::ROLE_SUPERADMIN){
                    $status     = Constant::OPTION_DISABLE;
                }
                if($role_update == Constant::ROLE_VENDOR ){
                    $status     = Constant::OPTION_DISABLE;
                }
            }elseif(in_array($role_login, $dispatcherRole)){
                $status         = Constant::OPTION_DISABLE;
            }elseif($role_login == Constant::ROLE_DRIVER){
                $status         = Constant::OPTION_DISABLE;
            }else{
                $status = Constant::OPTION_DISABLE;
            }

            if($status == Constant::OPTION_ENABLE){
                if($new_email != $users->email && $new_email != ""){

                    $checkEmail = User::where('email', $new_email)->first();
                    if ($checkEmail)
                    {
                        throw new ApplicationException("change_email.already_exist", ['email' => $new_email]);
                    }

                    $users->name        = $request->name;

                    if ($users->phonenumber != $request->phonenumber) {
                        $users->phonenumber = $request->phonenumber;
                    }

                    $users->status      = Constant::STATUS_INACTIVE;
                    $users->update();

                    $linkurl = env('URL_ADMIN_OPER');

                    $changeEmail = ChangeEmail::updateOrCreate(
                        ['old_email' => $users->email],
                        [
                            'old_email' => $users->email,
                            'new_email' => $new_email,
                            'token'     => str_random(60)
                         ]
                    );

                    if ($users && $changeEmail){
                        $users->notify(
                            new EmailActivation($changeEmail->new_email)
                        );

                        \Notification::route('mail', $changeEmail->new_email)
                        ->notify(new EmailActivationRequest($changeEmail->token,$linkurl));
                    }

                    $dataraw = '';
                    $reason  = 'Update user email and phonenumber ';
                    $trxid   = $users->id;
                    $model   = 'user';
                    EventLog::insertLog($trxid, $reason, $dataraw,$model);

                    return Response::success($users, 'messages.success_req_change_email');
                }else{

                    $users->name        = $request->name;
                    $users->phonenumber = $request->phonenumber;
                    $users->update();

                    $dataraw = '';
                    $reason  = 'Update user email and phonenumber ';
                    $trxid   = $users->id;
                    $model   = 'user';
                    EventLog::insertLog($trxid, $reason, $dataraw,$model);

                    return Response::success($users, 'messages.success_update_user');
                }
            }else{
                throw new ApplicationException("errors.access_denied");
            }
        }else{
            throw new ApplicationException("user.failure_save_user");
        }

    }

    public function change_client(Request $request, $id)
    {

        Validate::request($request->all(), [
            'id_enterprise' => 'required|int'
        ]);

        if($users = User::findOrFail($id)){
            $users->client_enterprise_identerprise = $request->client_enterprise_identerprise;
            $users->update();

            $dataraw = '';
            $reason  = 'Update user email and phonenumber ';
            $trxid   = $users->id;
            $model   = 'user';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($users);
        }else{
            throw new ApplicationException("user.failure_save_user");
        }
    }

    public function change_vendor(Request $request, $id)
    {

        Validate::request($request->all(), [
            'id_vendor' => 'required|int'
        ]);

        if($users = User::findOrFail($id)){
            $users->vendor_idvendor = $request->vendor_idvendor;
            $users->update();

            $dataraw = '';
            $reason  = 'Update id vendor user ';
            $trxid   = $users->id;
            $model   = 'user';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($users);
        }else{
            throw new ApplicationException("user.failure_save_user");
        }
    }

    public function destroy($id)
    {
        try {
            $users = User::where('id', $id)
            ->where('status',"!=",Constant::STATUS_DELETED)
            ->update([
                'status' => Constant::STATUS_DELETED
            ]);

            if ($users > 0) {
                $dataraw = '';
                $reason  = 'delete user ';
                $trxid   = $id;
                $model   = 'user';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success("user with id:{$id} deleted");
            }else{
                throw new ApplicationException("user.failure_save_user");
            }

        } catch (Exception $e) {
            throw new ApplicationException("user.failure_save_user");
        }

    }

    public function change_password(Request $request)
    {
        Validate::request($request->all(), [
            'old_password' => 'required|string',
            'password' => 'required|string|confirmed|between:8,16'
        ]);

        $user = User::find(auth()->guard('api')->user()->id);

        if(!Hash::check($request->old_password, $user->password)){
            throw new ApplicationException("user.old_password_incorrect");
        }

        $id = $request->user()->id;
        if($users = User::findOrFail($id)){
            $users->password = bcrypt($request->password);
            $users->update();

            $dataraw = '';
            $reason  = 'Change password user ';
            $trxid   = $users->id;
            $model   = 'user';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($users);
        }else{
            throw new ApplicationException("user.failure_save_user");
        }
    }



    /**
     * show vendor by name
     *
     * @param  [string] name
     * @return [json] vendor suspend object
     */
    public function suspend(Request $request,$id)
    {
        $role_login         = auth()->guard('api')->user()->idrole ;
        $idvendor_login     = auth()->guard('api')->user()->vendor_idvendor ;
        $actor_update       = User::where('id', $id)->first();
        $reason_suspend     = $request->reason_suspend;

        Validate::request($request->all(), [
            'reason_suspend'=> 'required|string' ,
        ]);

        $vendorUnderRole     = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND,
            Constant::ROLE_DRIVER,
            Constant::ROLE_EMPLOYEE,
        ];

        //if not superadmin or vendor
        if($role_login != Constant::ROLE_SUPERADMIN && $role_login != Constant::ROLE_VENDOR)
            throw new ApplicationException("errors.access_denied");

        //Superadmin can be deleted by superadmin
        if ($actor_update->idrole == Constant::ROLE_SUPERADMIN && $role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if ($role_login == Constant::ROLE_VENDOR) {
            if ($actor_update->vendor_idvendor != $idvendor_login)
                throw new ApplicationException("errors.access_denied");

            if (!in_array($actor_update->idrole , $vendorUnderRole))
                throw new ApplicationException("errors.access_denied");
        }

        try {
            $users  = User::where('id', $id)
                ->where('status',"=",Constant::STATUS_ACTIVE)
                ->update([
                    'status' => Constant::STATUS_SUSPENDED,
                    'reason_suspend' => $reason_suspend,
                    'updated_by' => $request->user()->id,
                ]);

            if ($users > 0) {
                $user = User::where('users.id', $id)->first();
                $user->notify(
                    new UserNotification("Your account has been suspended")
                );

                $array = array(
                    'user_id' => $id
                );
                $dataraw = json_encode($array);
                $reason  = 'Suspend user email ';
                $trxid   = $user->email;
                $model   = 'user';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success(['id' => $id], "user successfully suspended.");
            }else{
                throw new ApplicationException("user.failed_to_suspend", ['id' => $id]);
            }

        } catch (Exception $e) {
            throw new ApplicationException("user.failed_to_suspend", ['id' => $id]);
        }
    }


    /**
     * show vendor by name
     *
     * @param  [string] name
     * @return [json] vendor suspend object
     */
    public function activate(Request $request,$id)
    {
        $role_login         = auth()->guard('api')->user()->idrole ;
        $idvendor_login     = auth()->guard('api')->user()->vendor_idvendor ;
        $actor_update       = User::where('id', $id)->first();

        $vendorUnderRole     = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND,
            Constant::ROLE_DRIVER,
            Constant::ROLE_EMPLOYEE,
        ];

        if($role_login != Constant::ROLE_SUPERADMIN && $role_login != Constant::ROLE_VENDOR)
            throw new ApplicationException("errors.access_denied");

        //Superadmin can be deleted by superadmin
        if ($actor_update->idrole == Constant::ROLE_SUPERADMIN && $role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if ($role_login == Constant::ROLE_VENDOR) {
            if ($actor_update->vendor_idvendor != $idvendor_login)
                throw new ApplicationException("errors.access_denied");

            if (!in_array($actor_update->idrole , $vendorUnderRole))
                throw new ApplicationException("errors.access_denied");
        }

        try {
            $users  = User::where('id', $id)
                ->whereIn('status',[Constant::STATUS_SUSPENDED,Constant::STATUS_INACTIVE])
                ->update([
                    'status' => Constant::STATUS_ACTIVE,
                    'reason_suspend' => null,
                    'updated_by' => $request->user()->id,
                ]);

            if ($users > 0) {
                if ($users)
                    $user = User::where('users.id', $id)->first();
                    $user->notify(new UserNotification("Your account has been activated"));

                    $dataraw = '';
                    $reason  = 'Activate user ';
                    $trxid   = $user->id;
                    $model   = 'user';
                    EventLog::insertLog($trxid, $reason, $dataraw,$model);


                return Response::success(['id' => $id], "user successfully activate.");
            }else{
                throw new ApplicationException("user.failed_to_activate", ['id' => $id]);
            }

        } catch (Exception $e) {
            throw new ApplicationException("user.failed_to_activate", ['id' => $id]);
        }
    }


    public function deleteuser($id)
    {
        try {
            $count_user = User::where('id', $id)->first();

            if(empty($count_user)){
                throw new ApplicationException("errors.entity_not_found", ['entity' => 'User','id' => $id]);
            }

            $users = false;
            switch ($count_user->idrole) {
                case Constant::ROLE_VENDOR:
                    $jum_user   = User::where('vendor_idvendor', $count_user->vendor_idvendor)
                                    ->where('idrole',Constant::ROLE_VENDOR)
                                    ->count();

                    if($jum_user == 1){
                        throw new ApplicationException("user.user_only_one", ['id' => $id]);
                    }

                    $users = User::where('id', $id)
                            ->where('idrole',Constant::ROLE_VENDOR)
                            ->where('status',Constant::STATUS_SUSPENDED)
                            ->delete();
                    break;

                case Constant::ROLE_ENTERPRISE:
                    $jum_user   = User::where('client_enterprise_identerprise', $count_user->client_enterprise_identerprise)
                                    ->where('idrole',Constant::ROLE_ENTERPRISE)
                                    ->count();

                    if($jum_user == 1){
                        throw new ApplicationException("user.user_only_one", ['id' => $id]);
                    }

                    //cek user suspend tidak
                    if($count_user->status != Constant::STATUS_SUSPENDED ){
                        throw new ApplicationException("user.failure_delete_user_not_suspend", ['id' => $id]);
                    }

                    $jum_transaksi   = Order::where('client_userid', $id)
                                    ->count();

                    if ($jum_transaksi>0) {
                        throw new ApplicationException("errors.cannot_delete_account");
                    }

                    $users = User::where('id', $id)
                            ->where('idrole',Constant::ROLE_ENTERPRISE)
                            ->where('status',Constant::STATUS_SUSPENDED)
                            ->delete();
                    break;

                default:
                    throw new ApplicationException("user.failure_delete_user");
                    break;
            }
            if (!$users) {
                return Response::error("Could not delete users.");
            }else{

                $dataraw = '';
                $reason  = 'Delete user ';
                $trxid   = $id;
                $model   = 'user';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success("user with id:{$id} deleted");
            }

        } catch (Exception $e) {
            throw new ApplicationException("user.failure_delete_user");
        }

    }

}
