<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use Carbon\Carbon;
use App\Services\Response;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Drivertype;
use App\Constants\Constant;
use App\Services\Validate;
use App\PasswordReset;
use App\User;
use App\Models\ChangeEmail;
use App\Notifications\AccountActivation;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Notifications\AssignDriversToClient;
use App\Notifications\EmailActivation;
use App\Notifications\EmailActivationRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportingDriver;
use App\Http\Helpers\Paginator;
use App\Http\Helpers\EventLog;
use App\Models\Task;

use App\Exceptions\ApplicationException;
use App\Helper\MessageHelper;
use App\Http\Helpers\Notification;
use App\Models\DriverRequest;
use App\Models\MobileNotification;
use Illuminate\Support\Facades\Log;

class DriverController extends Controller
{

    /**
     * Get driver active
     *
     * @param [string] q
     * @param [string] id
     * @return [json] driver object
     */
    public function index(Request $request){
        //Query param
        $keyword_search     = $request->query('q');
        $driver_type        = $request->query('driver_type');
        $identerprise       = $request->query('identerprise');
        $status             = $request->query('status');
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown')  : Constant::OPTION_DISABLE ;
        $limit              = $request->query('limit');
        $orderBy            = $request->query('orderBy');
        $places             = $request->query('places');
        $assign_enterprise  = $request->query('assignenterprise');
        $idrequest          = $request->query('idrequest');

        //User info
        $idvendor_login     = auth()->guard('api')->user()->vendor_idvendor ;
        $role_login         = auth()->guard('api')->user()->idrole ;
        $idclient_login     = auth()->guard('api')->user()->client_enterprise_identerprise ;
        $user               = auth()->guard('api')->user();

        $Drivers            = Driver::where('users.idrole', Constant::ROLE_DRIVER)
                            ->join('users', 'driver.users_id', '=', 'users.id');

        //Status
        if(!empty($status)){
            $Drivers = $Drivers->where("users.status",$status);
        }else{
            $Drivers = $Drivers->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_SUSPENDED ]);
        }

        //Filter by vendor
        if($role_login == Constant::ROLE_VENDOR){
            $Drivers     = $Drivers->where("users.vendor_idvendor",$idvendor_login)
                ->leftjoin('client_enterprise', 'users.client_enterprise_identerprise' , '=', 'client_enterprise.identerprise')
                ->leftjoin('places', 'places.idplaces','=', 'driver.stay_idplaces');

            //Filter by assign enterprise
            if(!empty($assign_enterprise) && !empty($places)){
                $Drivers = $Drivers->select(
                    'users.*',
                    'driver.*',
                    'client_enterprise.name as ce_name',
                    'places.idplaces as idplaces',
                    DB::Raw("IF(`users`.`client_enterprise_identerprise` = ". $assign_enterprise .", true, false) as `checked`"),
                    DB::Raw("IF(`places`.`idplaces` = ". $places .", true, false) as `isplace`"))
                    ->with(["places" => function($query){
                        $query->select('idplaces', 'idplaces as value', 'name', 'latitude', 'longitude', 'identerprise');
                    }])
                    ->orderBy('checked', 'DESC')
                    ->orderBy('isplace', 'DESC')
                    ->orderBy('name', 'ASC');
            } else {
                if(!empty($assign_enterprise)){
                    $Drivers = $Drivers->select(
                        'users.*',
                        'driver.*',
                        'client_enterprise.name as ce_name',
                        DB::Raw("IF(`users`.`client_enterprise_identerprise` = ". $assign_enterprise .", true, false) as `checked`"))
                        ->with(["places" => function($query){
                            $query->select('idplaces', 'idplaces as value', 'name', 'latitude', 'longitude', 'identerprise');
                        }])
                        ->orderBy('checked', 'DESC')
                        ->orderBy('name', 'ASC');
                } else {
                    $Drivers = $Drivers->select(
                        'users.*',
                        'driver.*',
                        'client_enterprise.name as ce_name')
                    ->orderBy('name', 'ASC');
                }
            }
        }

        //Filter by enterprise
        if(!empty($identerprise) && in_array($role_login, [Constant::ROLE_SUPERADMIN,Constant::ROLE_VENDOR,Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])){
            $Drivers = $Drivers->where('users.client_enterprise_identerprise',$identerprise)->orderBy('name','ASC');
        }

        if (in_array($role_login, [Constant::ROLE_ENTERPRISE, Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])) {
            $Drivers    = $Drivers->where("users.client_enterprise_identerprise",$idclient_login)->orderBy('name', 'ASC');
        }

        if ($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER) {
            $Drivers    = $Drivers->where("users.vendor_idvendor",$idvendor_login);
        }

        //Filter by driver type
        if(!empty($driver_type)){
            if(in_array($user->idrole,[Constant::ROLE_SUPERADMIN,Constant::ROLE_VENDOR,Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS]))
            {
                if($driver_type == Constant::DRIVER_TYPE_PKWT){
                    $Drivers = $Drivers->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_PKWT);

                }else if($driver_type == Constant::DRIVER_TYPE_PKWT_BACKUP){
                    $Drivers = $Drivers->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_PKWT_BACKUP);

                }else if($driver_type == Constant::DRIVER_TYPE_FREELANCE){
                    $Drivers = $Drivers->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_FREELANCE);
                }
            }
        }
        //Dropdown
        if ($is_dropdown == Constant::OPTION_ENABLE) {
            //hardcode karena FE blm ada dropdown search
            $limit = 500;

            if(empty($assign_enterprise))
                $Drivers = $Drivers->select('users.id','users.name');

            //Search
            if(!empty($keyword_search))
                $Drivers = $Drivers->where("users.name","like","%".$keyword_search."%");

        }else{
            $Drivers = $Drivers->with(["user","drivertype"]);

            //Search
            if (!empty($keyword_search)) {
                $Drivers = $Drivers->where(function($query) use ($keyword_search) {
                    $query->where('users.name', 'like', '%' . $keyword_search . '%');
                });
            }
        }

        //OrderBy
        $Drivers = $Drivers->get();
        array_walk($Drivers, function (&$v, $k) {
            foreach ($v as $item) {
                if (!empty($item->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                }
                if (!empty($item->profil_picture_2)) {
                    $item->profil_picture_2 = env('BASE_API') . Storage::url($item->profil_picture_2);
                }
            }
        });

        $page = $request->page ? $request->page : 1 ;
        $perPage = $request->query('limit')?? Constant::LIMIT_PAGINATION;
        $all_driver = collect($Drivers);
        $driver_new = new Paginator($all_driver->forPage($page, $perPage), $all_driver->count(), $perPage, $page);
        $driver_new = $driver_new->setPath(url()->full());

        return Response::success($driver_new);
        // return Response::success($Drivers->paginate($limit ?? Constant::LIMIT_PAGINATION));
    }

    /**
     * save the specified vendor.
     *
     * @param  [string] name
     * @param  [string] birthdate
     * @param  [string] address
     * @param  [string] email
     * @param  [string] phonenumber
     * @param  [string] typedriver
     * @param  [string] idvendor
     * @param  [string] idrole
     * @param  [string] password
     * @return [json] Driver object
     */
    public function store(Request $request)
    {

        Validate::request($request->all(), [
            'name'=> 'required|min:3|max:45|string' ,
            'birthdate' => 'required',
            'address' => 'required|max:500|string' ,
            'email' => 'required|min:10|max:80|email|unique:users,email',
            'phonenumber' => 'required|min:10|max:45|string|unique:users,phonenumber',
            'nik' => 'required|min:16|max:16|string',
            'gender' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE,
            'attendance_latitude'   => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'attendance_longitude'  => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
        ]);

        DB::beginTransaction();
        try {
            $idvendor = auth()->guard('api')->user()->vendor_idvendor;
            $pass = rand(12345678,45678910);

            if($request->hasfile('photo')){
                $path = Storage::putFile("/public/images/users", $request->file('photo'));
            }else{
                $path = '';
            }

            $user = User::create([
                'name'  => $request->name,
                'email' => $request->email,
                'password'  => bcrypt($pass),
                'phonenumber'   => $request->phonenumber,
                'idrole'    => Constant::ROLE_DRIVER,
                'vendor_idvendor'   => $idvendor,
                'profile_picture'  => $path,
                'status'    => constant::STATUS_ACTIVE,
                'created_by'=> $request->user()->id
            ]);

            $Driver = Driver::create([
                'users_id' => $user->id,
                'birthdate' => $request->birthdate,
                'address' => $request->address,
                'drivertype_iddrivertype' => Constant::DRIVER_TYPE_PKWT_BACKUP,
                'nik' => $request->nik,
                'gender' => $request->gender,
                'attendance_latitude' => $request->attendance_latitude,
                'attendance_longitude' => $request->attendance_longitude,
                'created_by'=> $request->user()->id
            ]);



            $Drivers    = Driver::with(["user","drivertype"])
                        ->where('driver.users_id', $user->id)
                        ->first();

            $dataraw = '';
            $reason  = 'Create Driver user_id#';
            $trxid   = $user->id;
            $model   = 'driver';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();

            if ($user && $Driver)
                $user->notify(
                    new UserNotification("Your Pin Driver {$pass}")
            );

            return Response::success($Drivers);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("drivers.failure_save_driver");
        }

    }

    /**
     * show vendor by name
     *
     * @param  [string] name
     * @return [json] driver object
     */
    public function show($id)
    {
        $Drivers = Driver::select('driver.*', 'users.*')->with(["enterprise"])
            ->where('users.id', $id)
            ->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_INACTIVE, Constant::STATUS_SUSPENDED])
            ->join('users', 'driver.users_id', '=', 'users.id')->first();

        if(empty($Drivers)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'iddriver','id' => $id]);
        }

        // change image url to laravel path
        if(!empty($Drivers->profile_picture)){
            $Drivers->profile_picture = Storage::url($Drivers->profile_picture);
            $Drivers->profile_picture = env('BASE_API').$Drivers->profile_picture;
        }

        return Response::success($Drivers);
    }

    public function edit($id)
    {
        //
    }

    /**
     * Update the specified vendor.
     * @param  [string] id , id from user driver
     * @param  [string] name
     * @param  [string] phonenumber
     * @param  [string] address
     * @param  [string] phonenumber
     * @param  [string] typedriver
     * @return [string] message
     */
    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'name'          => 'required|min:3|max:45|string' ,
            'birthdate'     => 'required',
            'address'       => 'required|max:500|string',
            'phonenumber'   => 'required|min:10|max:45|string|unique:users,phonenumber,'.$id,
            'typedriver'    => 'string',
            'nik'           => 'required|min:16|max:16|string',
            'gender'        => 'required|string',
            'email'         => 'required|min:10|max:80|email',
            'attendance_latitude'   => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'attendance_longitude'  => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'photo'                 => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE
        ]);
        DB::beginTransaction();
        try {
            $new_email        = $request->email;
            $users            = User::findOrFail($id);
            if($new_email != $users->email && $new_email != ""){
                $this->updateEmailUserDriver($request, $id);
            }

            $Drivers = Driver::where('users_id',$id)->first();
            if ($Drivers == null)
                throw new ApplicationException("errors.entity_not_found", ['entity' => 'Driver', 'id' => $id]);

            $Users    = User::where('id',$id)->first();

            if($Drivers->drivertype_iddrivertype!=$request->typedriver){

                if($request->typedriver==Constant::DRIVER_TYPE_PKWT){
                    $update_user = $Users->update([
                        'client_enterprise_identerprise'=> $request->identerprise,
                        'updated_by'        => $request->user()->id
                    ]);
                }else{
                    $update_user = $Users->update([
                        'client_enterprise_identerprise'=> null,
                        'updated_by'        => $request->user()->id
                    ]);
                }
                $update1  = $Drivers->update([
                    'drivertype_iddrivertype'       => $request->typedriver,
                    'updated_by'        => $request->user()->id
                ]);
            }

            if($request->hasfile('photo')){
                $path = Storage::putFile("/public/images/users", $request->file('photo'));
                $user       = $Users->update([
                    'profile_picture'   => $path,
                    'updated_by'        => $request->user()->id
                ]);
            }

            $update = $Drivers->update([
                        'birthdate'         => $request->birthdate,
                        'address'           => $request->address,
                        'nik'               => $request->nik,
                        'gender'            => $request->gender,
                        'attendance_latitude'   => $request->attendance_latitude,
                        'attendance_longitude'  => $request->attendance_longitude,
                        'updated_by'        => $request->user()->id
                    ]);

        $user       = $Users->update([
                        'name'              => $request->name,
                        'phonenumber'       => $request->phonenumber,
                        'updated_by'        => $request->user()->id
                    ]);


            $Drivers    = Driver::with(["user","drivertype"])
                        ->where('driver.users_id', $id)
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->first();

            $dataraw = '';
            $reason  = 'Update Driver user_id#';
            $trxid   = $id;
            $model   = 'driver';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success($Drivers);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("drivers.failure_save_driver", ['id' => $id]);
        }

    }

    /**
     * delete driver
     *
     * @param  [string] id
     * @return [string] message
    */
    public function destroy($id)
    {
        $user = User::where('id',$id)->first();

        if($user->status == Constant::STATUS_SUSPENDED ){
            $jum_transaksi   = Order::where('driver_userid', $id)
                                ->count();

            if ($jum_transaksi>0) {
                throw new ApplicationException("errors.cannot_delete_account");
            }

            $driver = Driver::where('users_id',$id)->delete();
            $user = $user->delete();

            $dataraw = '';
            $reason  = 'Delete Driver user_id#';
            $trxid   = $id;
            $model   = 'driver';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("drivers.failure_delete_driver", ['id' => $id]);
        }

    }

    /**
     * driver type
     *
     *
     * @return [json] driver type
    */
    public function type()
    {
        $type = Drivertype::all();
        return Response::success($type);
    }

    /**
     * get list of available driver by vendor id
     * used for vendor to assign pkwt backup driver to enterprise
     *
     * @return [json] driver type
    */
    public function available(Request $request){

        if(auth()->guard('api')->user()->vendor_idvendor == null)
            throw new ApplicationException('errors.access_denied');

        $Drivers = Driver::select('users.name as name', 'users.phonenumber as phonenumber', 'users.email as email','Driver.*')
            ->where('users.status',"!=",Constant::STATUS_SUSPENDED)
            ->join('users', 'Driver.users_id', '=', 'users.id')
            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
            ->where('drivertype_iddrivertype',constant::DRIVER_TYPE_PKWT_BACKUP)
            ->get();

        return Response::success($Drivers);
    }

    /**
     * get list of available driver for order
     * used for vendor to assign pkwt backup driver to enterprise
     *
     * @return [json] driver type
    */
    public function available_for_order(Request $request){
        $identerprise     = $request->query('identerprise');
        $name             = $request->query('q');
        $is_dropdown      = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;

        if(auth()->guard('api')->user()->vendor_idvendor == null)
            throw new ApplicationException('errors.access_denied');

        if ($is_dropdown == Constant::OPTION_ENABLE) {

            if(!empty($identerprise)){
                //select driver dengan tipe pkwt dan sesuai client
                $validasi = Driver::select('users.id as id',DB::raw('CONCAT(users.name, " (", drivertype.name,")") AS name'))
                        ->where('users.status',"=",Constant::STATUS_ACTIVE)
                        ->leftJoin('users', 'driver.users_id', '=', 'users.id')
                        ->leftJoin('drivertype','drivertype.iddrivertype','=','driver.drivertype_iddrivertype')
                        ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                        ->where('driver.drivertype_iddrivertype',Constant::DRIVER_TYPE_PKWT)
                        ->where('users.client_enterprise_identerprise','=',$identerprise)
                        ->orderBy('drivertype_iddrivertype', 'asc');

                if(!empty($name)){
                    $validasi = $validasi->where("users.name","like","%".$name."%");
                }
                //tambah validasi checkin
                //pending
                // $validasi = $validasi->leftJoin('attendance','attendance.users_id','=','users.id')
                //             ->where("attendance.clock_in",">=",Carbon::today()->toDateString())
                //             ->whereNull("attendance.clock_out");

                $validasi        = $validasi->get();
                $page            = $request->page ? $request->page : 1 ;
                $perPage         = $request->query('limit')?? 500 ;

                if($validasi->count() == 0){
                    //ditutup karena ada query clockin
                    // $Drivers = Driver::select('users.id as id',DB::raw('CONCAT(users.name, " (", drivertype.name,")") AS name'))
                    //         ->where('users.status',"=",Constant::STATUS_ACTIVE)
                    //         ->leftJoin('users', 'driver.users_id', '=', 'users.id')
                    //         ->leftJoin('drivertype','drivertype.iddrivertype','=','driver.drivertype_iddrivertype')
                    //         ->leftJoin('attendance','attendance.users_id','=','users.id')
                    //         ->where("attendance.clock_in",">=",Carbon::today()->toDateString())
                    //         ->whereNull("attendance.clock_out")
                    //         ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                    //         ->where('driver.drivertype_iddrivertype','!=',Constant::DRIVER_TYPE_PKWT)
                    //         ->orderBy('drivertype_iddrivertype', 'asc');

                    $Drivers = Driver::select('users.id as id',DB::raw('CONCAT(users.name, " (", drivertype.name,")") AS name'))
                            ->where('users.status',"=",Constant::STATUS_ACTIVE)
                            ->leftJoin('users', 'driver.users_id', '=', 'users.id')
                            ->leftJoin('drivertype','drivertype.iddrivertype','=','driver.drivertype_iddrivertype')
                            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                            ->where('driver.drivertype_iddrivertype','!=',Constant::DRIVER_TYPE_PKWT)
                            ->orderBy('drivertype_iddrivertype', 'asc');

                    if(!empty($name)){
                        $Drivers = $Drivers->where("users.name","like","%".$name."%");
                    }

                    $Drivers = $Drivers->get();

                    // if($Drivers->count() == 0){
                    //     throw new ApplicationException("drivers.driver_full");
                    // }

                    $all_Drivers     = collect($Drivers);
                    $driver_new      = new Paginator($all_Drivers->forPage($page, $perPage), $all_Drivers->count(), $perPage, $page);
                    $driver_new      = $driver_new->setPath(url()->full());
                    return Response::success($driver_new);
                }

                $all_validasi      = collect($validasi);
                $validasi_new      = new Paginator($all_validasi->forPage($page, $perPage), $all_validasi->count(), $perPage, $page);
                $validasi_new      = $validasi_new->setPath(url()->full());
                return Response::success($validasi_new);
            }else{
                $Drivers = Driver::select('users.id as id',DB::raw('CONCAT(users.name, " (", drivertype.name,")") AS name'))
                        ->where('users.status',"=",Constant::STATUS_ACTIVE)
                        ->leftJoin('users', 'driver.users_id', '=', 'users.id')
                        ->leftJoin('drivertype','drivertype.iddrivertype','=','driver.drivertype_iddrivertype')
                        ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                        ->orderBy('drivertype_iddrivertype', 'asc')
                        ->get();
                return Response::success($Drivers);
            }
        }else{
            $Drivers = Driver::select('users.name as name', 'users.phonenumber as phonenumber', 'users.email as email','driver.*')
                    ->with("drivertype")
                    ->where('users.status',"=",Constant::STATUS_ACTIVE)
                    ->join('users', 'driver.users_id', '=', 'users.id')
                    ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                    ->orderBy('drivertype_iddrivertype', 'asc');

            if(!empty($name)){
                $Drivers = $Drivers->where("users.name","like","%".$name."%");
            }
            $Drivers = $Drivers->get();
            return Response::success($Drivers);
        }

    }

    private function updateEmailUserDriver(Request $request, $id){

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
        || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $role_update == Constant::ROLE_DISPATCHER_ONDEMAND || $role_update == Constant::ROLE_EMPLOYEE ){
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
                    $users->status = Constant::STATUS_INACTIVE;
                    $users->update();

                    $checkEmail = User::where('email', $new_email)->first();
                    if ($checkEmail)
                    {
                        throw new ApplicationException("change_email.already_exist", ['email' => $new_email]);
                    }

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

                    DB::table('oauth_access_tokens')
                        ->where('user_id', $id)
                        ->update([
                            'revoked' => true
                        ]);
                }
            }
        }
    }

    public function assign_to_enterprise(Request $request){

        Validate::request($request->all(), [
            'identerprise'          => 'integer|required',
            'userdata'              => 'array|nullable',
            'userdata.*.id'         => 'integer|required',
            'unassign_ids'          => 'array|nullable',
            'idrequest'             => 'integer|nullable',
            'time'                  => 'string|nullable'
        ]);

        $idvendor = $request->idvendor;

        if(!empty($idvendor)){
            if($idvendor != env('OLX_IDVENDOR')){
                Validate::request($request->all(), [
                    'userdata.*.idplaces'   => 'integer|required',
                ]);
            }
        }

        // Assign new drivers with the location
        DB::beginTransaction();

        $newlyAddedDrivers = [];
        $arrayId = [];
        $time = $request->time;
        foreach ($request->userdata as $index => $newDriver){

            if($user = User::
                where("id",$newDriver["id"])
                ->first()){

                array_push($arrayId, $newDriver["id"]);
                $user->client_enterprise_identerprise = $request->identerprise;
                $user->update();

                $dataraw = '';
                $reason  = 'Assign Driver to enterprise #';
                $trxid   = $newDriver["id"];
                $model   = 'driver';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                // if current driver status is PKWT Backup update it to PKWT
                if($driver = Driver::with("user")->where('users_id', $newDriver["id"])
                    ->first()){
                    $driver->updated_by = auth()->guard('api')->user()->id;
                    $driver->drivertype_iddrivertype = constant::DRIVER_TYPE_PKWT;
                    //update location
                    $driver->stay_idplaces = $newDriver["idplaces"];
                    //update driver time
                    if(!empty($time)){
                        $driver->stay_time = $time;
                    }
                    $driver->update();
                    $newlyAddedDrivers[$index] = $driver;
                }else{
                    DB::rollBack();
                    throw new ApplicationException("drivers.driver_not_found",["id" => $newDriver["id"]]);
                }

            }else{
                DB::rollBack();
                throw new ApplicationException("drivers.driver_not_found",["id" => $newDriver["id"]]);
            }

        }

        // Unassign drivers
        if(count($request->unassign_ids) > 0){
            foreach ($request->unassign_ids as $index => $unassignedDriver){

                if($user = User::
                    where("id",$unassignedDriver)
                    ->first()){

                    $user->client_enterprise_identerprise = null;
                    $user->update();

                    $dataraw = '';
                    $reason  = 'Unassign Driver to enterprise #';
                    $trxid   = $unassignedDriver;
                    $model   = 'driver';
                    EventLog::insertLog($trxid, $reason, $dataraw,$model);

                    // if current driver status is PKWT update it to PKWT Backup
                    if($driver = Driver::with("user")->where('users_id', $unassignedDriver)
                        ->first()){
                        if ($driver->drivertype_iddrivertype == constant::DRIVER_TYPE_PKWT){
                            $driver->updated_by = auth()->guard('api')->user()->id;
                            $driver->drivertype_iddrivertype = constant::DRIVER_TYPE_PKWT_BACKUP;
                            $driver->update();
                        }
                    }else{
                        DB::rollBack();
                        throw new ApplicationException("drivers.driver_not_found",["id" => $unassignedDriver]);
                    }

                }else{
                    DB::rollBack();
                    throw new ApplicationException("drivers.driver_not_found",["id" => $unassignedDriver]);
                }

            }
        }


        DB::commit();


        $idrequest = $request->idrequest;
        $reqAmt = null;
        //change request status
        if(!empty($idrequest)){
            DriverRequest::where('id', $idrequest)
                ->update([
                    'status' => 2
                ]);
            //get request amount and notes
            $driverReq = DriverRequest::where('id', $idrequest)->first();
            $reqAmt = $driverReq->number_of_drivers;
            $notes = $driverReq->note;
        }

        if(!empty($idvendor)){
            if($idvendor != env('OLX_IDVENDOR')){
                //notification by wa to dispatcher
                //get dispatcher from enterprise id
                $dispatcher = DB::table('client_enterprise')
                    ->join('users', 'users.client_enterprise_identerprise', '=', 'client_enterprise.identerprise')
                    ->where('client_enterprise.identerprise', $request->identerprise)
                    ->where('users.idrole', Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS)
                    ->selectRaw('users.phonenumber, users.name')
                    ->first();

                //Get vendor's admin phone number
                $phoneNumbers = DB::table('client_enterprise')
                    ->where('client_enterprise.identerprise', $request->identerprise)
                    ->join('users', 'client_enterprise.vendor_idvendor' , '=', 'users.vendor_idvendor')
                    ->where('users.idrole', Constant::ROLE_VENDOR)
                    ->selectRaw('users.phonenumber, users.name')
                    ->get();

                //get sorted driver data with location
                $drivers = DB::table('driver')
                    ->join('users', 'users.id', '=', 'driver.users_id')
                    ->join('places', 'idplaces', '=', 'stay_idplaces')
                    ->whereIn('users.id', $arrayId)
                    ->selectRaw('users.id as iduser, places.idplaces as id, users.name as name, places.name as location, users.phonenumber as phonenumber, driver.stay_time as time')
                    ->orderBy('id', 'ASC')
                    ->get();

                if(!empty($time))
                    $date = Carbon::parse($time)->format('d-m-Y, H:i:s');
                else
                    $date = Carbon::tomorrow()->format('d-m-Y');

                $listDriverString = "";
                $listLocation = [];
                $iter = 0;
                $driverAmt = $drivers->count();

                foreach($drivers as $index => $driver){
                    if(array_search($driver->id, $listLocation, true) === false){
                        $driverCount = $drivers->where('location', $driver->location)->count();
                        $iter = 1;
                        array_push($listLocation, $driver->id);
                        $listDriverString = $listDriverString . ("Lokasi: {$driver->location}\n\n");
                    }
                    $phone_number = "62" . substr($driver->phonenumber, 1);
                    $listDriverString = $listDriverString . ("{$iter}.{$driver->name}\nWA: https://wa.me/{$phone_number}\n\n");
                    $iter++;
                }

                $messaging = new MessageHelper();

                try{
                    //request amount must not be null
                    if($reqAmt != null){
                        //vendor notification
                        foreach ($phoneNumbers as $key => $value) {
                            $messaging->sendMessage(
                                MessageHelper::WHATSAPP,
                                $value->phonenumber,
                                (
                                    "Halo, {$value->name}. Berikut rincian penempatan driver untuk tanggal {$date}\n\n".
                                    "Jumlah Request Driver: " . $reqAmt . "\n" .
                                    "Keterangan: " . $notes . "\n" .
                                    "Jumlah Penempatan Driver: " . $driverAmt . "\n" .
                                    $listDriverString .
                                    "Terima Kasih~"
                                )
                            );
                        }
                        //dispatcher notification
                        $messaging->sendMessage(
                            MessageHelper::WHATSAPP,
                            $dispatcher->phonenumber,
                            (
                                "Halo, {$dispatcher->name}. Berikut rincian penempatan driver untuk tanggal {$date}\n\n".
                                "Jumlah Request Driver: " . $reqAmt . "\n" .
                                "Keterangan: " . $notes . "\n" .
                                "Jumlah Penempatan Driver: " . $driverAmt . "\n" .
                                $listDriverString .
                                "Terima Kasih~"
                            )
                        );
                    }
                } catch (Exception $e) {
                    throw new ApplicationException("notifications.failure");
                }


                foreach ($drivers as $index => $driver){


                    if($user = User::
                        where("id",$driver->iduser)
                        ->first()){
                        // send email to driver when assign to client enterprise

                        /* Untuk sekarang notif by email ditutup dulu
                        $detailEnterprise = User::where('idrole', Constant::ROLE_ENTERPRISE)
                                ->with(["enterprise","role"])
                                ->where('client_enterprise_identerprise', $request->identerprise)
                                ->first();

                        $enterprise =
                        [
                            'greeting' => 'Assign Driver To Client Enterprise',
                            'line' => [
                                'Name Client'   => $detailEnterprise->name,
                                'Description'   => $detailEnterprise->enterprise->description,
                                'email'         => $detailEnterprise->email,
                                'office phone'  => $detailEnterprise->enterprise->office_phone,
                                'office address' => $detailEnterprise->enterprise->office_address,
                                'pic name'      => $detailEnterprise->enterprise->pic_name,
                                'pic phone'     => $detailEnterprise->enterprise->pic_phone,
                                'site url'      => $detailEnterprise->enterprise->site_url ,
                            ],
                        ];

                        $user->notify(
                            new AssignDriversToClient($enterprise)
                        );
                        */

                        //Mobile app notification
                        $tokenMobile = MobileNotification::where("user_id", $driver->iduser)
                            ->first();

                        $fcmRegIds = array();

                        if (!empty($tokenMobile))
                            array_push($fcmRegIds, $tokenMobile->token);

                        if ($fcmRegIds) {
                            $title           = "Update Waktu dan Lokasi Stay";
                            $messagebody     = "Lokasi Stay: {$driver->location}, Tanggal/Waktu: {$driver->time}";
                            $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody);
                            $returnsendorder = Notification::sendNotification($getGenNotif);
                            if ($returnsendorder == false) {
                                Log::critical("failed send Notification  : {$driver->iduser} ");
                            }
                        } else {
                            Log::critical("failed send Notification  : {$driver->iduser} ");
                        }
                    }
                }
            }
        }

        return Response::success($newlyAddedDrivers);
    }


    /**
     * id user driver
     *
     *
     * @return [json] driver type
    */
    public function resendpin(Request $request)
    {
        Validate::request($request->all(), [
            'id_user' => 'required|integer|exists:users,id'
        ]);

        $user = auth()->guard('api')->user();

        switch ($user->idrole) {

            case Constant::ROLE_SUPERADMIN:
                $Drivers = Driver::select('driver.*', 'users.*')
                        ->where('users.id', $request->id_user)
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->first();
            break;

            case Constant::ROLE_VENDOR:
                $Drivers = Driver::select('driver.*', 'users.*')
                        ->where('users.id', $request->id_user)
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->Where('users.vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                        ->first();
            break;

            default:
            break;
        }

        if(empty($Drivers)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'id user driver','id' => $request->id_user]);
        }


        $pass = rand(12345678,45678910);

        $user2 = User::where('id', $request->id_user)
                    ->update([
                        'password'  => bcrypt($pass),
                        'created_by'=> $request->user()->id
                    ]);

        $user = User::where('id', $request->id_user)->first();
        if ($user)
            $user->notify(
                new UserNotification("Your Pin Driver {$pass}")
        );

        return Response::success($user);
    }


    public function orderdriver(Request $request)
    {
        $idtemplate   = $request->idtemplate;
        $daterange    = $request->daterange;

        if (empty($idtemplate) || empty($daterange)) {
            throw new ApplicationException("errors.template_daterange");
        }

        $file_name    = "ReportingDriver".$idtemplate."-".$daterange.".xlsx";
        Excel::store(new ReportingDriver($idtemplate, $daterange), '/public/file/' . $file_name);
        $fileexport = Storage::url('file/' . $file_name);
        return Response::success(["file export" => url($fileexport) ] );

    }

    public function totalAccount()
    {
        $user = auth()->guard('api')->user();

        $driver         = Driver::select(DB::raw('count(*) as total_driver'))
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->where('users.idrole', Constant::ROLE_DRIVER)
                        ->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_SUSPENDED ]);

        $driveractive   = Driver::select(DB::raw('count(*) as total_driver_active'))
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->where('users.idrole', Constant::ROLE_DRIVER)
                        ->where('users.status', Constant::STATUS_ACTIVE);

        $driversuspend   = Driver::select(DB::raw('count(*) as total_driver_suspend'))
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->where('users.idrole', Constant::ROLE_DRIVER)
                        ->where('users.status', Constant::STATUS_SUSPENDED);

        switch ($user->idrole) {

            case Constant::ROLE_VENDOR:
                $driver         = $driver->where("users.vendor_idvendor", $user->vendor_idvendor);
                $driveractive   = $driveractive->where("users.vendor_idvendor", $user->vendor_idvendor);
                $driversuspend  = $driversuspend->where("users.vendor_idvendor", $user->vendor_idvendor);
            break;
            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $driver         = $driver->where("users.client_enterprise_identerprise", $user->client_enterprise_identerprise);
                $driveractive   = $driveractive->where("users.client_enterprise_identerprise", $user->client_enterprise_identerprise);
                $driversuspend  = $driversuspend->where("users.client_enterprise_identerprise", $user->client_enterprise_identerprise);
            break;
            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $driver         = $driver->where("users.vendor_idvendor", $user->vendor_idvendor);
                $driveractive   = $driveractive->where("users.vendor_idvendor", $user->vendor_idvendor);
                $driversuspend  = $driversuspend->where("users.vendor_idvendor", $user->vendor_idvendor);
            break;
        }

        $driver         = $driver->first();
        $driveractive   = $driveractive->first();
        $driversuspend  = $driversuspend->first();

        $report                         = new \stdClass();
        $report->total_driver           = $driver->total_driver;
        $report->total_driver_active    = $driveractive->total_driver_active;
        $report->total_driver_suspend   = $driversuspend->total_driver_suspend;

        return Response::success($report);
    }
}
