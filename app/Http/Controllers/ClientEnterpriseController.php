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
use App\Models\EnterpriseType;
use App\Models\ClientEnterprise;
use App\Models\Inspector;
use App\Models\Order;
use App\Models\Vendor;
use App\Exceptions\ApplicationException;
use Illuminate\Support\Facades\Storage;
use App\Http\Helpers\Paginator;
use App\Http\Helpers\EventLog;

class ClientEnterpriseController extends Controller
{
    
    /**
     * Get Client enterprise type
     *
     * @return [json] EnterpriseType object
     */
    public function type()
    {
        return Response::success(EnterpriseType::all());
    }

    /**
     * Get Client enterprise list
     *
     * @param  [int] limit
     * @param  [int] page
     * @param [string] q
     * @param [string] id
     * @return [json] ClientEnterprise object
     */
    public function index(Request $request)
    {
        // Query param
        $keyword_search     = $request->query('q');
        $status             = $request->query('status');
        $is_dropdown        = $request->query('dropdown');
        $typeenterprise     = $request->query('typeenterprise');
        $user               = auth()->guard('api')->user();
        $orderBy            = $request->query('orderBy');

        $clientEnterprise   = ClientEnterprise::select('client_enterprise.*');

        if (!empty($status)) {
            $clientEnterprise = $clientEnterprise->where("client_enterprise.status", $status);
        } else {
            $clientEnterprise = $clientEnterprise->where('client_enterprise.status', '=', Constant::STATUS_ACTIVE);
        }

        if (!empty($typeenterprise)) {
            $clientEnterprise = $clientEnterprise->where("client_enterprise.enterprise_type_identerprise_type", $typeenterprise);
        }
      
        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $clientEnterprise = $clientEnterprise->select('client_enterprise.identerprise', 'client_enterprise.name');
            
            if (!empty($keyword_search)) {
                $clientEnterprise = $clientEnterprise->where("client_enterprise.name", "like", "%".$keyword_search."%");
            }
        } else {
            $clientEnterprise = $clientEnterprise->with(["vendor","enterprise_type","users"]);

            if (!empty($keyword_search)) {
                $clientEnterprise  = $clientEnterprise->where(function ($query) use ($keyword_search) {
                    $query  ->where('client_enterprise.name', 'like', '%' . $keyword_search . '%');
                });
            }
        }
        switch ($user->idrole) {
            case constant::ROLE_VENDOR:
                // only show order in that client_enterprise only
                $clientEnterprise->Where('client_enterprise.vendor_idvendor', '=', $user->vendor_idvendor);
                break;
        }


        //OrderBy
        $clientEnterprise->orderBy('identerprise', $orderBy ?? 'DESC');
        $clientEnterprise = $clientEnterprise->get();
        $no = 1;
        array_walk($clientEnterprise, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->image_logo)) {
                    $item->image_logo = env('BASE_API') . Storage::url($item->image_logo);
                }
            }
            $no++;
        });

        $ni = 1;
        array_walk($clientEnterprise, function (&$v, $k) use ($ni) {
            foreach ($v as $items) {
                // $item->ni = $ni;
                if (!empty($items->users)) {
                    foreach ($items->users as $user) {
                        if (!empty($user->profile_picture)) {
                            $user->profile_picture = env('BASE_API') . Storage::url($user->profile_picture);
                        }
                    }
                }
            }
            // $ni++;
        });
        
        $page = $request->page ? $request->page : 1 ;
        $perPage = $request->query('limit')?? 9;
        $all_clientEnterprise = collect($clientEnterprise);
        $clientEnterprise_new = new Paginator($all_clientEnterprise->forPage($page, $perPage), $all_clientEnterprise->count(), $perPage, $page); 
        $clientEnterprise_new = $clientEnterprise_new->setPath(url()->full());
        return Response::success($clientEnterprise_new);

        // return Response::success($clientEnterprise->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
    }

    /**
     * Get Client enterprise detail
     *
     * @param  [int] id
     * @return [json] ClientEnterprise object
     */
    public function show($id)
    {
        $status = [
            Constant::STATUS_ACTIVE,
            Constant::STATUS_INACTIVE,
            Constant::STATUS_SUSPENDED
        ];

        $clientEnterprise = ClientEnterprise::with(["vendor","enterprise_type","dispatcher","admins"])
            ->where('identerprise',$id)
            ->whereIn('status',$status)
            ->first();
        if (!empty($clientEnterprise->image_logo)) {
            $clientEnterprise->image_logo = Storage::url($clientEnterprise->image_logo);
            $clientEnterprise->image_logo = env('BASE_API').$clientEnterprise->image_logo;
        }
        
        if (!empty($clientEnterprise->dispatcher->profile_picture)) {
            $clientEnterprise->dispatcher->profile_picture = Storage::url($clientEnterprise->dispatcher->profile_picture);
            $clientEnterprise->dispatcher->profile_picture = env('BASE_API').$clientEnterprise->dispatcher->profile_picture;
        }

        $no=0;
        if (!empty($clientEnterprise->admins)) {
            array_walk($clientEnterprise->admins, function (&$v, $k) use ($no) {
                foreach ($v as $item) {
                    $item->no = $no;
                    if (!empty($item->profile_picture)) {
                        $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                    }
                }
            });
        }

        if(empty($clientEnterprise)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'identerprise','id' => $id]);
        }
        
        return Response::success($clientEnterprise);

    }

    /**
     * Create Client Enterprise
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] description
     * @param  [int] identerprise_type from enterprise type table
     * @param  [int] idvendor from vendor table
     * @param  [string] office_phone
     * @param  [string] office_address
     * @return [string] pic_name
     * @return [string] pic_phone
     * @return [boolean] is_private
     */
    public function store(Request $request)
    {        
		Validate::request($request->all(), [
            // client enterprise part
            'name' => 'required|min:3|max:45|string',
            'description' => 'nullable|max:500|string',
            'email' => 'required|min:10|max:80|email|unique:client_enterprise',
            'identerprise_type' => 'required|integer',
            'idvendor' => 'required|integer|exists:vendor',
            'office_phone' => 'required|min:10|max:45|string',
            'office_address' => 'required|max:500|string',
            'site_url'  => 'required|min:8|max:100|string|URL',
            'image_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE,
            // client enterprise pic part
            'pic_name' => 'required|min:3|max:45|string',
            'pic_phone' => 'required|min:10|max:45|string|',
            'pic_email' => 'required|min:10|max:80|email',
            // admin client enterprise part
            'admin_name' => 'required|min:3|max:45|string',
            'admin_email' => 'required|min:10|max:80|email|unique:users,email',
            'admin_phone' => 'required|min:10|max:45|string|unique:users,phonenumber',
            // PIC task part
            'inspectors' => 'array|required',
            'inspectors.*.name' => 'required|min:3|max:45|string',
            'inspectors.*.phone' => 'required|min:10|max:45|string'
        ]);
        

        DB::beginTransaction();
        try {
            if($request->hasfile('image_logo')){
                $path = Storage::putFile("/public/images/enterprise", $request->file('image_logo'));             

            }else{
                $path = '';
            } 

            // create client enterprise
            $enterprise = ClientEnterprise::create([
                'name' => $request->name,
                'email' => $request->email,
                'description' => $request->description ?? "",
                'vendor_idvendor' => $request->idvendor,
                'enterprise_type_identerprise_type' => $request->identerprise_type,
                'office_phone' => $request->office_phone,
                'office_address' => $request->office_address,
                'pic_name' => $request->pic_name,
                'pic_phone' => $request->pic_phone,
                'pic_email' => $request->pic_email,
                'site_url' => $request->site_url,
                'image_logo' => $path,
                'is_private' => $request->is_private ?? false,
                'created_by' => auth()->guard('api')->user()->id,
                'status'    => Constant::STATUS_INACTIVE
            ]);
            
            // $linkurl = $request->site_url;
            // create default admin for client enterprise
            try {
    
                $user = User::create([
                    'name' => $request->admin_name,
                    'email' => $request->admin_email,
                    'password' => bcrypt(str_random(12)),
                    'phonenumber' => $request->admin_phone,
                    'idrole' => constant::ROLE_ENTERPRISE,
                    'vendor_idvendor' => $enterprise->vendor_idvendor,
                    'client_enterprise_identerprise' => $enterprise->identerprise,
                    'created_by' => auth()->guard('api')->user()->id,
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
    
                // create client enterprise inspectors
                foreach ($request->inspectors as $index => $inspector){
                    try {
                        
                        $newInspector = Inspector::create([
                            'client_enterprise_identerprise' => $enterprise->identerprise,
                            'name' => $inspector["name"],
                            'phonenumber' => $inspector["phone"],
                            'status'   => '1',
                            'created_by' => auth()->guard('api')->user()->id,
                        ]);
    
                    }catch (Exception $e) {   
                        DB::rollBack();                
                        throw new ApplicationException("inspector.failure_save_inspector");
                    }
                }
                
                $dataraw = '';
                $reason  = 'Create Client Enterprise #';
                $trxid   = $enterprise->identerprise;
                $model   = 'Client Enterprise';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);
                
                DB::commit();
                
                if ($user && $passwordReset)
                    $user->notify(
                        new AccountActivation($passwordReset->token,$request->site_url)
                    );

                $clientEnterprise = ClientEnterprise::with(["vendor","enterprise_type","dispatcher","admins","inspectors"])
                    ->where('identerprise',$enterprise->identerprise)
                    ->first();

                return Response::success($clientEnterprise);

            } catch (Exception $e) {
                DB::rollBack();
                throw new ApplicationException("user.failure_save_user");
            }

        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("clients.failure_save_client_enterprise");
        }
        
    }

    /**
     * Update Client Enterprise
     *
     * @param  [string] name
     * @param  [string] email
     * @param  [string] description
     * @param  [int] identerprise_type from enterprise type table
     * @param  [int] idvendor from vendor table
     * @param  [string] office_phone
     * @param  [string] office_address
     * @return [string] pic_name
     * @return [string] pic_phone
     * @return [boolean] is_private
     */
    public function update(Request $request, $id)
    {
        $enterprise = ClientEnterprise::where('identerprise', $id)->first();

        if($request->email == $enterprise->email) {
            $email ='required|min:10|max:80|string';
        }else{
            $email ='required|min:10|max:80|string|unique:client_enterprise,email';
        }      
		Validate::request($request->all(), [
            'name' => 'required|min:3|max:45|string',
            'email' => $email,
            'identerprise_type' => 'required|integer',
            'idvendor' => 'required|integer|exists:vendor',
            'office_phone' => 'required|min:10|max:45|string',
            'office_address' => 'required|max:500|string',
            'pic_name' => 'required|min:3|max:45|string',
            'pic_phone' => 'required|min:10|max:45|string',
            'site_url'  => 'required|min:8|max:100|string|URL',
            'pic_email' => 'required|min:10|max:80|email',
            'description' => 'nullable|max:500|string',
            'image_logo' => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE,
        ]);

        try {
            if($request->hasfile('image_logo')){
                $path = Storage::putFile("/public/images/enterprise", $request->file('image_logo'));
                    
            }else{
                $path = '';
            } 

            $enterprise = ClientEnterprise::where('identerprise', $id)
                ->update([
                    'name' => $request->name,
                    'email' => $request->email,
                    'description' => $request->description ?? "",
                    'vendor_idvendor' => $request->idvendor,
                    'enterprise_type_identerprise_type' => $request->identerprise_type,
                    'office_phone' => $request->office_phone,
                    'office_address' => $request->office_address,
                    'pic_name' => $request->pic_name,
                    'pic_phone' => $request->pic_phone,
                    'pic_email' => $request->pic_email,
                    'site_url' => $request->site_url,
                    'image_logo' => $path,
                    'is_private' => $request->is_private ?? false,
                    'updated_by' => auth()->guard('api')->user()->id
                ]);

            $clientEnterprise = ClientEnterprise::with(["vendor","enterprise_type","dispatcher","admins","inspectors"])
                ->where('identerprise',$id)->first();
    
            $dataraw = '';
            $reason  = 'Update Client Enterprise #';
            $trxid   = $id;
            $model   = 'Client Enterprise';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($clientEnterprise);

        } catch (Exception $e) {
            throw new ApplicationException("clients.failure_save_client_enterprise");
        }
        
    }

    /**
     * Mark client enterprise status as deleted
     *
     * @param  [int] id
     * @return [json] ClientEnterprise object
     */
    public function destroy($id)
    {
        $enterprise = ClientEnterprise::where('identerprise',$id)->first();

        if($enterprise->status == Constant::STATUS_SUSPENDED ){ 
            $jum_transaksi   = Order::where('client_enterprise_identerprise', $id)
                            ->count();

            if ($jum_transaksi>0) {
                throw new ApplicationException("errors.cannot_delete_account");
            }

            $user       = User::where('client_enterprise_identerprise',$id)->delete();
            $inspector  = Inspector::where('client_enterprise_identerprise',$id)->delete();
            $enterprise = $enterprise->delete();

            $dataraw = '';
            $reason  = 'Delete Client Enterprise #';
            $trxid   = $id;
            $model   = 'Client Enterprise';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("clients.failure_delete_client_enterprise", ['id' => $id]);
        }
           
    }

    public function suspend(Request $request)
    {
        Validate::request($request->all(), [
            'identerprise' => 'required|integer|exists:client_enterprise',
            'reason_suspend'=> 'required|string',
        ]);
        $reason_suspend     = $request->reason_suspend;
        $id                 = $request->identerprise;

        $role_login         = auth()->guard('api')->user()->idrole ;
        $actor_update       = ClientEnterprise::where('identerprise', $id)
                            ->where('status',"=",Constant::STATUS_ACTIVE)
                            ->first();
        
        //if not superadmin or vendor
        if($role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if(empty($actor_update)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'enterprise','id' => $id]);
        }

        try {
            $enterprise  = ClientEnterprise::where('identerprise', $id)
                    ->where('status',"=",Constant::STATUS_ACTIVE)
                    ->update([
                        'status' => Constant::STATUS_SUSPENDED,
                        'reason_suspend' => $reason_suspend,           
                        'updated_by' => $request->user()->id,
                    ]);

            if ($enterprise > 0) {
                $dataraw = '';
                $reason  = 'Suspend Client Enterprise #';
                $trxid   = $id;
                $model   = 'Client Enterprise';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success(['id' => $id], "Enterprise successfully suspended.");
            }else{
                throw new ApplicationException("client.failed_to_suspend", ['id' => $id]);
            }
            
        } catch (Exception $e) {
            throw new ApplicationException("client.failed_to_suspend", ['id' => $id]);
        }

    }


    public function activate(Request $request)
    {
        Validate::request($request->all(), [
            'identerprise' => 'required|integer|exists:client_enterprise',
        ]);
        $id                 = $request->identerprise;

        $role_login         = auth()->guard('api')->user()->idrole ;
        $actor_update       = ClientEnterprise::where('identerprise', $id)
                            ->where('status',"=",Constant::STATUS_SUSPENDED)
                            ->first();

        //if not superadmin or vendor
        if($role_login != Constant::ROLE_SUPERADMIN)
            throw new ApplicationException("errors.access_denied");

        //jika user vendor maka idrole yg bisa di suspend itu dispatcher dan driver dan id vendor si driver atau dispatcher itu sesuai id vendor user login
        if(empty($actor_update)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'enterprise','id' => $id]);
        }

        try {
            $enterprise  = ClientEnterprise::where('identerprise', $id)
                    ->where('status',"=",Constant::STATUS_SUSPENDED)
                    ->update([
                        'status' => Constant::STATUS_ACTIVE,  
                        'reason_suspend' => null,                           
                        'updated_by' => $request->user()->id,
                    ]);

            if ($enterprise > 0) {
                $dataraw = '';
                $reason  = 'Activate Client Enterprise #';
                $trxid   = $id;
                $model   = 'Client Enterprise';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success(['id' => $id], "Enterprise successfully activate.");
            }else{
                throw new ApplicationException("client.failed_to_activate", ['id' => $id]);
            }
            
        } catch (Exception $e) {
            throw new ApplicationException("client.failed_to_activate", ['id' => $id]);
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
            'identerprise' => 'required|integer|exists:client_enterprise',
        ]);

        $enterprise       = ClientEnterprise::where('identerprise', $request->identerprise)
                            ->first();

        //jika user vendor tidak ada butuh url nya
        if (empty($enterprise)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'ENTERPRISE','id' => $request->identerprise]);
        }            

        $user = User::where('idrole', Constant::ROLE_ENTERPRISE)
                ->where('status', Constant::STATUS_INACTIVE)
                ->where('client_enterprise_identerprise', $request->identerprise)
                ->first();

        //jika user vendor tidak ada
        if(empty($user)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'ENTERPRISE','id' => $request->identerprise]);
        }


        $passwordReset = PasswordReset::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => str_random(60)
             ]
        );

        if ($user && $passwordReset && $enterprise){
            $user->notify(new AccountActivation($passwordReset->token, $enterprise->site_url));
        }

        return Response::success(['identerprise' => $request->identerprise], "Succesfully Send Link Activation");

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
            'identerprise' => 'required|integer|exists:client_enterprise',
            'admin_name' => 'required|string|max:45',
            'admin_email' => 'required|max:100|email|unique:users,email',
            'admin_mobile_number' => 'required|string|unique:users,phonenumber',
        ]);

        try {

            $clientEnterprise = ClientEnterprise::where('identerprise',$request->identerprise)
                                ->first();
            $idvendor         = $clientEnterprise->vendor_idvendor;
            $linkurl          = $clientEnterprise->site_url;

            $user = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => bcrypt(str_random(12)),
                'phonenumber' => $request->admin_mobile_number,
                'idrole' => constant::ROLE_ENTERPRISE,
                'vendor_idvendor' => $idvendor,
                'client_enterprise_identerprise' => $request->identerprise,
                'created_by' => auth()->guard('api')->user()->id,
                'status' => constant::STATUS_INACTIVE
            ]);
            
            $passwordReset = PasswordReset::updateOrCreate(
                ['email' => $user->email],
                [
                    'email' => $user->email,
                    'token' => str_random(60)
                 ]
            );
        

            DB::commit();

            if ($user && $passwordReset){
                $user->notify(
                    new AccountActivation($passwordReset->token,$linkurl)
                );
            }
                
            return Response::success($user);

        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("clients.failure_save_client_enterprise");
        }

    }

}
