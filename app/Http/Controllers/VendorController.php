<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Response;
use App\Services\Validate;
use App\Notifications\AccountActivation;
use App\User;
use App\PasswordReset;
use DB;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\ApplicationException;
use App\Models\Vendor;
use App\Constants\Constant;
use App\Http\Helpers\EventLog;

class VendorController extends Controller
{

    
    /**
     * Get vendor active
     * @param [string] q
     * @param [string] id
     * @return [json] vendor object
     */
    public function index(Request $request)
    {
        // Query param
        $keyword_search     =  $request->query('q');
        $status             =  $request->query('status');
        $is_dropdown        =  $request->query('dropdown') ?  $request->query('dropdown'): Constant::OPTION_DISABLE ;
        $orderBy            = $request->query('orderBy');
        
       // $datavendor         = vendor::where('users.idrole',Constant::ROLE_VENDOR)
         //                   ->join('users','users.vendor_idvendor','=','vendor.idvendor');

       $datavendor          = vendor::select('vendor.*');
        
        if(!empty($status)){
            $datavendor = $datavendor->where("vendor.status",$status);
        }else{
            $datavendor = $datavendor->where('vendor.status','=',Constant::STATUS_ACTIVE);
        }

        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $datavendor = $datavendor->select('vendor.idvendor','vendor.name');
            if(!empty($keyword_search))
                $datavendor = $datavendor->where("vendor.name","like","%".$keyword_search."%");
            
        }else{
                $datavendor = $datavendor->select('vendor.*')
                              ->with(["users"]);
            
            if(!empty($keyword_search)){
                $datavendor = $datavendor->where(function($query) use ($keyword_search) {
                                $query->where('vendor.name','like','%'.$keyword_search.'%') 
                                ->orwhere('vendor.email','like','%'.$keyword_search.'%') 
                                ->orwhere('vendor.office_phone_number','like','%'.$keyword_search.'%') ;
                            });
            } 
            
        }

        //OrderBy
        $datavendor->orderBy('idvendor', $orderBy ?? 'DESC');

        return Response::success($datavendor->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
    }

    public function create()
    {
    }

    /**
     * save the specified vendor.
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] office_phone_number
     * @param  [string] office_address
     * @param  [string] pic_name
     * @param  [string] pic_mobile_number
     * @return [json] vendor object
     */
    public function admin(Request $request)
    {
        Validate::request($request->all(), [
            'idvendor'=> 'required|integer|exists:vendor' ,
            'admin_name' => 'required|string|max:45',
            'admin_email' => 'required|max:100|email|unique:users,email',
            'admin_mobile_number' => 'required|string|unique:users,phonenumber|max:45|min:10',
        ]);

        try {

            $user = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => bcrypt(str_random(12)),
                'phonenumber' => $request->admin_mobile_number,
                'idrole' => Constant::ROLE_VENDOR,
                'vendor_idvendor' => $request->idvendor,
                'status'    => Constant::STATUS_INACTIVE
            ]);
            
            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => str_random(60)
                 ]
            );
            
            $dataraw = '';
            $reason  = 'Create Admin Vendor #';
            $trxid   = $request->idvendor;
            $model   = 'vendor';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();

            $linkurl = env('URL_VENDOR');

            if ($user && $passwordReset)
                $user->notify(
                    new AccountActivation($passwordReset->token,$linkurl)
                );

            return Response::success($user);

        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("vendors.failure_save_vendor");
        }

    }

    /**
     * save the specified vendor.
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] office_phone_number
     * @param  [string] office_address
     * @param  [string] pic_name
     * @param  [string] pic_mobile_number
     * @return [json] vendor object
     */
    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'name'=> 'required|min:3|max:45|string' ,
            'email' => 'required|min:10|max:80|email|unique:vendor,email',
            'office_phone_number' => 'required|min:10|max:45|string' ,
            'office_address' => 'required|max:500|string',
            'pic_name' => 'required|min:3|max:45|string',
            'pic_mobile_number' => 'required|min:10|max:45|string',
            'pic_email' => 'required|min:10|max:80|email',
            'admin_name' => 'required|min:3|max:45|string',
            'admin_mobile_number' => 'required|min:10|max:45|string|unique:users,phonenumber',
            'admin_email' => 'required|min:10|max:80|email|unique:vendor,email'
        ]);
        
        DB::beginTransaction();

        try {
            
            $vendor = vendor::create([
                'name' => $request->name,
                'email' => $request->email,
                'office_phone_number' => $request->office_phone_number,
                'office_address' => $request->office_address,
                'pic_name' => $request->pic_name,
                'pic_mobile_number' => $request->pic_mobile_number,
                'pic_email' => $request->pic_email,
                'created_by'=> $request->user()->id,
                'status'    => Constant::STATUS_INACTIVE
            ]);

            $user = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => bcrypt(str_random(12)),
                'phonenumber' => $request->admin_mobile_number,
                'idrole' => Constant::ROLE_VENDOR,
                'vendor_idvendor' => $vendor->idvendor,
                'status'    => Constant::STATUS_INACTIVE
            ]);
            
            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => str_random(60)
                 ]
            );

            $dataraw = '';
            $reason  = 'Create Vendor #';
            $trxid   = $vendor->idvendor;
            $model   = 'vendor';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            $linkurl = env('URL_VENDOR');

            if ($user && $passwordReset)
                $user->notify(
                    new AccountActivation($passwordReset->token,$linkurl)
                );
                
            return Response::success($vendor);

        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("vendors.failure_save_vendor");
        }

    }

    /**
     * show vendor by id
     *
     * @param  [int] id
     * @return [json] vendor object
     */
    public function show($id)
    {
        $status = [
            Constant::STATUS_ACTIVE,
            Constant::STATUS_INACTIVE,
            Constant::STATUS_SUSPENDED
        ];

        $vendor = Vendor::with(['admins'])
            ->where('idvendor', $id)
            ->whereIn('vendor.status',$status)
            ->first();
        
        if(empty($vendor)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'vendor','id' => $id]);
        }
        
        return Response::success($vendor);
    }

    public function edit($id)
    {
        //
    }

    /**
     * Update the specified vendor.
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] password
     * @param  [string] office_phone_number
     * @param  [string] office_address
     * @param  [string] pic_name
     * @param  [string] pic_mobile_number
     * @return [string] message
     */
    public function update(Request $request, $id)
    {          
        $vendor = Vendor::where('idvendor', $id)->first();

        if($request->email == $vendor->email) {
            $email ='required|min:10|max:80|email';
        }else{
            $email ='required|min:10|max:80|email|unique:vendor,email';
        }      
        Validate::request($request->all(), [
            'name'=> 'required|min:3|max:45|string' ,
            'email' => $email,
            'office_phone_number' => 'required|min:10|max:45|string' ,
            'office_address' => 'required|max:500|string',
            'pic_name' => 'required|min:3|max:45|string',
            'pic_mobile_number' => 'required|min:10|max:45|string',
            'pic_email' => 'required|min:10|max:80|email'
        ]);
        
        $name = $request->name;
        $email = $request->email;
        $office_phone_number = $request->office_phone_number;
        $office_address = $request->office_address;
        $pic_name = $request->pic_name;
        $pic_mobile_number = $request->pic_mobile_number;
        $pic_email = $request->pic_email;
        $updated_by = $request->updated_by;

        DB::beginTransaction();
        try {
            $vendors = vendor::where('idvendor',$id)->update(
                [
                    'email' => $email,
                    'office_phone_number' => $office_phone_number,
                    'office_address' => $office_address,
                    'pic_name' => $pic_name,
                    'pic_mobile_number' => $pic_mobile_number,
                    'pic_email' => $pic_email,
                    'updated_by' => $request->user()->id,
                    'name' => $name,
                ]);   

            $dataraw = '';
            $reason  = 'Update Vendor #';
            $trxid   = $id;
            $model   = 'vendor';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("vendors.failure_save_vendor", ['id' => $id]);
        }

    }

    public function destroy(Request $request,$id)
    {
        $vendors = Vendor::with('admins')
            ->where('idvendor',$id)->first();

        dd($vendors->admins->name);

        if($vendors->status == Constant::STATUS_SUSPENDED ){
            $user = User::where('vendor_idvendor',$id)->delete();
            $vendors = $vendors->delete();

            $dataraw = '';
            $reason  = 'Delete Vendor #';
            $trxid   = $id;
            $model   = 'vendor';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("vendors.failure_delete_vendor", ['id' => $id]);  
        }
                        

    }


    /**
     * show vendor by name
     *
     * @param  [string] name
     * @return [json] vendor suspend object
     */
    public function suspend(Request $request)
    {
        Validate::request($request->all(), [
            'idvendor' => 'required|integer|exists:vendor',
            'reason_suspend' => 'required|string',
        ]);

        $reason_suspend     = $request->reason_suspend;
        $id                 = $request->idvendor;

        $role_login         = auth()->guard('api')->user()->idrole ;
        $actor_update       = Vendor::where('idvendor', $id)
                            ->where('status',"=",Constant::STATUS_ACTIVE)
                            ->first();

        //if not superadmin or vendor
        if($role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if(empty($actor_update)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'vendor','id' => $id]);
        }

        try {
            $vendor  = Vendor::where('idvendor', $id)
                    ->where('status',"=",Constant::STATUS_ACTIVE)
                    ->update([
                        'status' => Constant::STATUS_SUSPENDED,
                        'reason_suspend' => $reason_suspend,               
                        'updated_by' => $request->user()->id,
                    ]);

            $dataraw = '';
            $reason  = 'Suspend Vendor #';
            $trxid   = $id;
            $model   = 'vendor';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            if ($vendor > 0) {
                return Response::success(['id' => $id], "Vendor successfully suspended.");
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
    public function activate(Request $request)
    {
        Validate::request($request->all(), [
            'idvendor' => 'required|integer|exists:vendor',
        ]);

        $id                 = $request->idvendor;
        $role_login         = auth()->guard('api')->user()->idrole ;
        $actor_update       = Vendor::where('idvendor', $id)->first();

        //if not superadmin or vendor
        if($role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if(empty($actor_update)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'vendor','id' => $id]);
        }

        try {
            $vendor  = Vendor::where('idvendor', $id)
                    ->whereIn('status', [Constant::STATUS_INACTIVE, Constant::STATUS_SUSPENDED])
                    ->update([
                        'status' => Constant::STATUS_ACTIVE,  
                        'reason_suspend' => null,                
                        'updated_by' => $request->user()->id,
                    ]);
        
            if ($vendor > 0) {
                $dataraw = '';
                $reason  = 'Activate Vendor #';
                $trxid   = $id;
                $model   = 'vendor';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success(['id' => $id], "Vendor successfully activate.");
            }else{
                throw new ApplicationException("vendors.failed_to_activate", ['id' => $id]);
            }

        } catch (Exception $e) {
            throw new ApplicationException("vendors.failed_to_activate", ['id' => $id]);
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
            'idvendor' => 'required|integer|exists:vendor',
        ]);

        $user = User::where('idrole', Constant::ROLE_VENDOR)
                ->where('status', Constant::STATUS_INACTIVE)
                ->where('vendor_idvendor', $request->idvendor)
                ->first();

        //jika user vendor tidak ada
        if(empty($user)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'vendor','id' => $request->idvendor]);
        }
        
        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );

        $linkurl = env('URL_VENDOR');

        if ($user && $passwordReset) {
            $user->notify(new AccountActivation($passwordReset->token, $linkurl));
        }

        return Response::success(['idvendor' => $request->idvendor], "Succesfully Send Link Activation");

    }


}
