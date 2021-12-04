<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Dispatcher;
use Carbon\Carbon;
use App\Services\Response;
use App\Services\Validate;
use App\Constants\Constant;
use App\User;
use App\PasswordReset;
use App\Models\ChangeEmail;
use App\Models\Order;
use App\Exceptions\ApplicationException;
use App\Notifications\AccountActivation;
use App\Notifications\AssignDispatcherToClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Facades\Storage;
use App\Notifications\EmailActivation;
use App\Notifications\EmailActivationRequest;
use DB;
use App\Http\Helpers\DataHelper;
use App\Http\Helpers\Paginator;
use App\Http\Helpers\EventLog;

class DispatcherController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @param [string] q
     * @param [string] id
     * @param  $role[int] array of user roleid
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Query param
        $keyword_search     = $request->query('q');
        $status             = $request->query('status');
        $typedispatcher     = $request->query('typedispatcher');
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;
        $orderBy            = $request->query('orderBy');

        $dispatcherRole = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];
        
        if(empty(auth()->guard('api')->user()->vendor_idvendor)){
            throw new ApplicationException("errors.access_denied");
        }

        if ($request->query('role')) {

            try {
                
                $dispatcher = User::whereIn('idrole', $dispatcherRole)
                                ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor);
                
            } catch (Exception $e) {
                throw new ApplicationException("dispatcher.failure_get_dispatcher");
            }

        } else {
            try {
                $dispatcher = User::whereIn('idrole', $dispatcherRole)
                                ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor);

            } catch (Exception $e) {
                throw new ApplicationException("dispatcher.failure_get_dispatcher");
            }
            
        } 
        
        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $dispatcher = $dispatcher->select('users.id','users.name');

            if(!empty($keyword_search))
                $dispatcher = $dispatcher->where("users.name","like","%".$keyword_search."%");
            
        }else{
            $dispatcher = $dispatcher->with(["vendor","enterprise","role","dispatcher_profile"]);
            
            if(!empty($keyword_search)){
                $dispatcher        = $dispatcher->where(function($query) use ($keyword_search) {
                                    $query->where('users.name','like','%' . $keyword_search . '%');
                });
            }  
        }
        
        if(!empty($status)){
            $dispatcher = $dispatcher->where("users.status",$status);
        }else{
            $dispatcher = $dispatcher->where('users.status','=',Constant::STATUS_ACTIVE);
        }

        if (!empty($typedispatcher)) {
            $dispatcher = $dispatcher->where("users.idrole",$typedispatcher);
        }

        //OrderBy
        $dispatcher->orderBy('users.id', $orderBy ?? 'DESC');

        $dispatcher = $dispatcher->get();
        $no = 1;
        array_walk($dispatcher, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                }
            }
            $no++;
        });

        $page = $request->page ? $request->page : 1 ;
        $perPage = $request->query('limit')?? Constant::LIMIT_PAGINATION;
        $all_dispatcher = collect($dispatcher);
        $dispatcher_new = new Paginator($all_dispatcher->forPage($page, $perPage), $all_dispatcher->count(), $perPage, $page, [
            'path' => url("attendance/reporting?type=driver")
        ]); 
        return Response::success($dispatcher_new);
        // return Response::success($dispatcher->paginate($request->query('limit')  ?? Constant::LIMIT_PAGINATION));
    }
    /**
     * Display the specified resource.
     *
     * @param  \App\Dispatcher 
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        // Query param
        $dispatcherRole = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];

        if(empty(auth()->guard('api')->user()->vendor_idvendor)){
            throw new ApplicationException("errors.access_denied");
        }

        if (empty($request->query('role'))) {

            try {
                $dispatcher = User::with(["vendor","enterprise","role","dispatcher_profile"])
                                ->whereIn('idrole', $dispatcherRole)
                                ->whereIn('status', [Constant::STATUS_ACTIVE, Constant::STATUS_INACTIVE, Constant::STATUS_SUSPENDED])
                                ->where('id', $id)
                                ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                                ->first();
                
            } catch (Exception $e) {
                throw new ApplicationException("dispatcher.failure_get_dispatcher");
            }

        }else{
            $roles = explode(',',$request->query('role') );

            foreach ($roles as $index => $role) {
                if (!in_array($role, $dispatcherRole )) {                    
                    throw new ApplicationException("dispatcher.failure_get_dispatcher_by_role");
                }
            }
            
            try {
                
                $dispatcher = User::with(["vendor","enterprise","role","dispatcher_profile"])
                                ->whereIn('idrole', $roles)
                                ->whereIn('status', [Constant::STATUS_ACTIVE, Constant::STATUS_SUSPENDED])
                                ->where('id', $id)
                                ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                                ->first();

            } catch (Exception $e) {
                throw new ApplicationException("dispatcher.failure_get_dispatcher");
            }
            
        } 

        // change image url to laravel path
        if(!empty($dispatcher->profile_picture)){
            $dispatcher->profile_picture = Storage::url($dispatcher->profile_picture);
            $dispatcher->profile_picture = env('BASE_API').$dispatcher->profile_picture;
        }

        return Response::success($dispatcher);

    }

    /**
     * get list of dispatcher by vendor and identerprise
     * get dispatcher regular
     * 
     * 
     * @return [json] driver type
    */
    public function available(Request $request)
    {     
        $keyword_search     = $request->query('q');
        if(auth()->guard('api')->user()->vendor_idvendor == null) 
            throw new ApplicationException('errors.access_denied');

        $dispatchers = User::where('users.status', Constant::STATUS_ACTIVE)
            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
            ->join('dispatcher','dispatcher.users_id','=','users.id')
            ->where('idrole',constant::ROLE_DISPATCHER_ENTERPRISE_REGULER);

        if(!empty($keyword_search)){
            $dispatchers = $dispatchers->where(function($query) use ($keyword_search) {
                                $query->where('users.name', 'like', '%' . $keyword_search . '%');
                                $query->orwhere('users.email', 'like', '%' . $keyword_search . '%');
                            });
        }
        
        $dispatchers = $dispatchers->get();

        return Response::success($dispatchers);
    }

    public function assign_to_enterprise(Request $request){

        Validate::request($request->all(), [
            'dispatcher_userid'  => 'required|integer|exists:users,id' ,
            'identerprise'  => 'required|integer|exists:client_enterprise' ,
        ]);

        //get user dispatcher
        $users = User::find($request->dispatcher_userid);
        if (empty($users))
            throw new ApplicationException('errors.entity_not_found', ['entity' => 'Dispatcher', 'id' => $request->dispatcher_userid]);

        //check dispatcher is already assigned
        if ($users->idrole == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS)
            throw new ApplicationException('dispatcher.failure_dispatcher_have_been_assign');
            
        //check is dispatcher regular
        if ($users->idrole != Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER)
            throw new ApplicationException('dispatcher.failure_dispatcher_not_regular');
           
        //get old dispatcher if any
        $old_dispatcher = User::where('idrole',constant::ROLE_DISPATCHER_ENTERPRISE_PLUS)
            ->where('client_enterprise_identerprise', $request->identerprise)
            ->get();
                      
        DB::beginTransaction();
        
        try {
            //ubah status dipatcher plus ke dispatcher biasa
            foreach($old_dispatcher as $dispatcher_detail) {
                $update_dispatcher = User::where('id', $dispatcher_detail->id)
                    ->update([
                        'client_enterprise_identerprise' => NULL,
                        'idrole' => constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
                    ]);
            }

            //update 
            $users->client_enterprise_identerprise = $request->identerprise;
            $users->idrole = constant::ROLE_DISPATCHER_ENTERPRISE_PLUS;
            $users->update();

            //send email to client
            $qdispatcher = Dispatcher::where('users_id', $request->dispatcher_userid)
                    ->with(["user"]) 
                    ->first();

            if (empty($qdispatcher))
                throw new ApplicationException('errors.entity_not_found', ['entity' => 'Dispatcher', 'id' => $request->dispatcher_userid]);

            $dispatcherdetail = 
            [
                'greeting' => 'You Have New Dispatcher',
                'line' => [
                    'name'      => $qdispatcher->user->name,
                    'email'     => $qdispatcher->user->email,
                    'nik'       => $qdispatcher->nik,
                    'Birthdate' => $qdispatcher->birthdate,
                    'Address'   => $qdispatcher->address,
                ],
            ];

            

            //send email to dispatcher
            $dispatcherRole = [
                Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
                Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
                Constant::ROLE_DISPATCHER_ONDEMAND
            ];

            DB::commit();

            $users->notify(
                new AssignDispatcherToClient($dispatcherdetail)
            );

            $emailsDispatcher = User::wherein('idrole', $dispatcherRole)
                                ->where('id', $request->dispatcher_userid)
                                ->first();
                
            if($emailsDispatcher){   
                $detailEnterprise = User::where('idrole', Constant::ROLE_ENTERPRISE)
                        ->with(["enterprise","role"])
                        ->where('client_enterprise_identerprise', $request->identerprise)
                        ->first();   
                
                $enterprise = 
                [
                    'greeting' => 'Assign Dispatcher To Client Enterprise',
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

                $emailsDispatcher->notify(
                    new AssignDispatcherToClient($enterprise)); 
            }  


            return Response::success($users);
        }
        catch (Exception $e) {
            DB::rollBack();         
            throw new ApplicationException("dispatcher.failure_assign_dispatcher");
        }
    }

    /**
     * save the specified vendor.
     *
     * @param  [string] name
     * @param  [string] birthdate
     * @param  [string] address
     * @param  [string] email
     * @param  [string] phonenumber
     * @param  [string] photo
     * @param  [string] nik
     * @param  [string] idvendor
     * @param  [string] gender
     * @return [json] Driver object
     */
    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'name'=> 'required|min:3|max:45|string' ,
            'email' => 'required|min:10|max:80|email|unique:users,email',
            'phonenumber' => 'required|min:10|max:45|string|unique:users,phonenumber',
            'birthdate' => 'required',
            'address' => 'required|max:500|string' ,
            'nik' => 'required|min:16|max:16|string',
            'gender' => 'required|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE
        ]);

        DB::beginTransaction();
        try {  
            if($request->hasfile('photo')){
                $path = Storage::putFile("/public/images/users", $request->file('photo'));             

            }else{
                $path = '';
            } 
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt(str_random(12)),
                'phonenumber' => $request->phonenumber,
                'idrole' => Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
                'vendor_idvendor' => auth()->guard('api')->user()->vendor_idvendor,
                'profile_picture'  => $path,
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

            $dispatcher = Dispatcher::create([
                'users_id' => $user->id,
                'birthdate' => $request->birthdate,
                'nik' => $request->nik,
                'gender' => $request->gender,
                'address' => $request->address,
                'created_by'=> $request->user()->id
            ]);   
            $dispatcher = $dispatcher->where('users_id', $user->id)
                ->with(["user"]) 
                ->first();

            $dataraw = '';
            $reason  = 'Create Dispatcher user_id#';
            $trxid   = $user->id;
            $model   = 'dispatcher';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();       
           
            $linkurl = env('URL_VENDOR');

            if ($user && $passwordReset){
                $user->notify(
                    new AccountActivation($passwordReset->token,$linkurl)
                );    
            }

            if(!empty($dispatcher->user->profile_picture)){
                $dispatcher->user->profile_picture = Storage::url($dispatcher->user->profile_picture);    
                $dispatcher->user->profile_picture = env('BASE_API').$dispatcher->user->profile_picture;
            }       
                     
            return Response::success($dispatcher);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("dispatcher.failure_save_dispatcher");
        }

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
            'name'=> 'required|min:3|max:45|string' ,
            'phonenumber' => 'required|min:10|max:45|string|unique:users,phonenumber,'.$id,
            'birthdate' => 'required',
            'address' => 'required|string' ,
            'nik' => 'required|max:16|max:16|string',
            'gender' => 'required|string',
            'idrole'        => 'required|string',
            'email'         => 'required|min:10|max:80|email',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE
        ]);

        DB::beginTransaction();
        try {
            $new_email    = $request->email;
            $dispatchers  = Dispatcher::where('users_id',$id)->first();
            $Users        = User::where('id',$id)->first();

            if(empty($dispatchers)){
                throw new ApplicationException("errors.entity_not_found", ['entity' => 'Dispatcher','id' => $id]);            
            }

            if($new_email != $Users->email && $new_email != ""){
                $this->updateEmailUser($request, $id);
            }

            if($request->hasfile('photo')){
                $path = Storage::putFile("/public/images/users", $request->file('photo'));
                    
            }else{
                $path = '';
            } 
            $update = $dispatchers->update([
                        'birthdate'     => $request->birthdate,
                        'nik'           => $request->nik,
                        'gender'        => $request->gender,
                        'address'       => $request->address,
                        'updated_by'    => auth()->guard('api')->user()->id,
                    ]);   

            $user   = $Users->update([
                        'name'              => $request->name,
                        'phonenumber'       => $request->phonenumber,
                        'vendor_idvendor'   => auth()->guard('api')->user()->vendor_idvendor,
                        'profile_picture'   => $path,
                        'idrole'            => $request->idrole,
                        'updated_by'        => auth()->guard('api')->user()->id,
                    ]); 

            $dispatcher2 = User::with(["vendor","enterprise","role","dispatcher_profile"])                   
                    ->where('users.id', $id)
                    ->first();

            $dataraw = '';
            $reason  = 'update Dispatcher user_id#';
            $trxid   = $id;
            $model   = 'dispatcher';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success($dispatcher2);   
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("drivers.failure_save_driver", ['id' => $id]);
        }
    }


    public function destroy($id)
    {
        $user = User::where('id',$id)->first();

        if($user->status == Constant::STATUS_SUSPENDED){ 
            $jum_transaksi   = Order::where('dispatcher_userid', $id)
                            ->count();

            if ($jum_transaksi>0) {
                throw new ApplicationException("errors.cannot_delete_account");
            }

            $user = $user->delete();
            $dispatcher = Dispatcher::where('users_id', $id)->delete();

            $dataraw = '';
            $reason  = 'Delete Dispatcher user_id#';
            $trxid   = $id;
            $model   = 'dispatcher';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);


            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("dispatcher.failure_delete_dispatcher", ['id' => $id]);  
        }
    }


    private function updateEmailUser(Request $request, $id){
        
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
                    $users->status = Constant::STATUS_INACTIVE;
                    $users->update();
                
                    $checkEmail = User::where('email', $new_email)->first();
                    if ($checkEmail)
                    {
                        throw new ApplicationException("change_email.already_exist", ['email' => $new_email]);
                    }
                    
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
                $reason  = 'Update Email user Dispatcher user_id#';
                $trxid   = $id;
                $model   = 'dispatcher';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                    DB::table('oauth_access_tokens')
                        ->where('user_id', $id)
                        ->update([
                            'revoked' => true
                        ]);
                }
            }
        }
    }
    

    /**
     * resend activation 
     *
     * @param  [integer] idvendor
     * 
     */
    public function resendactivation(Request $request)
    {
        Validate::request($request->all(), [
            'iddispatcher' => 'required|integer|exists:dispatcher',
        ]);

        $dispatcher       = Dispatcher::where('iddispatcher', $request->iddispatcher)
                            ->first();

        //jika user vendor tidak ada butuh url nya
        if (empty($dispatcher)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'Dispatcher','id' => $request->iddispatcher]);
        }

        $dispatcherRole = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];

        $user = User::whereIn('idrole', $dispatcherRole)
                    ->where('status', Constant::STATUS_INACTIVE)
                    ->where('id', $dispatcher->users_id)
                    ->first();

        //jika user vendor tidak ada
        if (empty($user)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'DISPATCHER','id' => $request->iddispatcher]);
        }


        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );

        $linkurl = env('URL_VENDOR');
        if ($user && $passwordReset && $dispatcher){
            $user->notify(new AccountActivation($passwordReset->token, $linkurl));
        }

        return Response::success(['iddispatcher' => $request->iddispatcher], "Succesfully Send Link Activation");

    }

    public function totalAccount()
    {
        // $total      = DataHelper::getDispatcherTotalAccount();

        $dispatcherRole = [
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];
        

        if(empty(auth()->guard('api')->user()->vendor_idvendor)){
            throw new ApplicationException("errors.access_denied");
        }        

        $users          = User::whereIn('idrole', $dispatcherRole)
                            ->whereIn('status',[Constant::STATUS_ACTIVE,Constant::STATUS_SUSPENDED])
                            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                            ->count();
        $users_active   = User::whereIn('idrole', $dispatcherRole)
                            ->where('status', Constant::STATUS_ACTIVE)
                            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                            ->count();
        $users_suspend  = User::whereIn('idrole', $dispatcherRole)
                            ->where('status', Constant::STATUS_SUSPENDED) 
                            ->where('vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                            ->count();

        $response   = new \stdClass();
        $response->total_dispatcher   = $users;
        $response->active_account     = $users_active;
        $response->suspended_account  = $users_suspend;
        return Response::success($response);
    }
}
