<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use Carbon\Carbon;
use App\Services\Response;
use App\Models\EmployeePosition;
use App\Models\Vendor;
use App\Constants\Constant;
use App\Services\Validate;
use App\PasswordReset;
use App\User;
use App\Notifications\AccountActivation;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Notifications\AssignDriversToClient;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Helpers\EventLog;

use App\Exceptions\ApplicationException;

class EmployeePositionController extends Controller
{

    /**
     * Get driver active
     *
     * @param [string] q
     * @param [string] id
     * @return [json] Employee object
     */
    public function index(Request $request){ 
        $keyword_search     = $request->query('q');
        $is_dropdown        = $request->query('month') ? $request->query('month') : Constant::OPTION_DISABLE ;


        $position           = EmployeePosition::where('status','=',Constant::STATUS_ACTIVE);

        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $position = $position->select('idemployee_position','job_name');
        }

        if(!empty($keyword_search))
            $position = $position->where("job_name","like","%".$keyword_search."%");

        if(auth()->guard('api')->user()->idrole == Constant::ROLE_VENDOR){
            $position->where("vendor_idvendor",auth()->guard('api')->user()->vendor_idvendor);
        }

        return Response::success($position->paginate($request->query('limit') ?? 10));
    }

    /**
     * save the specified vendor.
     *
     * @param  [string] name
     * @param  [string] birthdate
     * @param  [string] address
     * @param  [string] email
     * @param  [string] phonenumber
     * @param  [string] idvendor
     * @param  [string] idrole
     * @param  [string] password
     * @return [json] Employee object
     */
    public function store(Request $request)
    {
        $user   = auth()->guard('api')->user()->vendor_idvendor;
        $vendor = vendor::select('vendor.*')->where('idvendor', $user)->first(); 

        if ($vendor->show_employee_price == 1) {
            $price = 'required|numeric|digits_between:1,20';
        } else {
            $price = 'numeric|digits_between:1,20';
        }

        Validate::request($request->all(), [
            'job_name' => 'required|min:3|max:45|string',
            'price'    => $price,
        ]);

        DB::beginTransaction();
        try {  

            $position = EmployeePosition::create([
                'job_name'  => $request->job_name,
                'price'  => $request->price,
                'vendor_idvendor'  => auth()->guard('api')->user()->vendor_idvendor,
                'status'    => constant::STATUS_ACTIVE,
                'created_by' => auth()->guard('api')->user()->id
            ]);

            $dataraw = '';
            $reason  = 'Create Employee Position #';
            $trxid   = $position->idemployee_position;
            $model   = 'employee position';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success($position);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("employeeposition.failure_save_employee_position");
        }

    }

    /**
     * show vendor by name
     *
     * @param  [string] name
     * @return [json] employee object
     */
    public function show($id)
    { 
        $position = EmployeePosition::where('idemployee_position', $id)
                    ->where('status','!=',Constant::STATUS_DELETED)
                    ->first();           
   
        if(empty($position)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'idemployeeposition','id' => $id]);            
        }
        
        return Response::success($position);
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
        $user   = auth()->guard('api')->user()->vendor_idvendor;
        $vendor = vendor::select('vendor.*')->where('idvendor', $user)->first(); 

        if ($vendor->show_employee_price == 1) {
            $price = 'required|numeric|digits_between:1,20';
        } else {
            $price = 'numeric|digits_between:1,20';
        }

        Validate::request($request->all(), [
            'job_name' => 'required|min:3|max:45|string',
            'price'    => $price,
        ]);
        
        DB::beginTransaction();
        try {
            $position = EmployeePosition::where('idemployee_position', $id)->first();

            if ($position == null)
                throw new ApplicationException("errors.entity_not_found", ['entity' => 'idemployeeposition', 'id' => $id]);

            $update   = $position->update([
                            'job_name' => $request->job_name,
                            'price' => $request->price,
                            'updated_by' => auth()->guard('api')->user()->id
                        ]);   

            $dataraw = '';
            $reason  = 'Update Employee Position #';
            $trxid   = $id;
            $model   = 'employee position';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success($position);   
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("employee.failure_save_employee", ['id' => $id]);
        }
    }
    
    /**
     * delete driver
     *
     * @param  [string] id
     * @return [string] message
    */
    public function destroy(Request $request,$id)
    {    
        try {
            $users = EmployeePosition::where('idemployee_position', $id)
                    ->where('status',"!=",Constant::STATUS_DELETED)
                    ->update([
                        'status' => Constant::STATUS_DELETED
                    ]);

            $dataraw = '';
            $reason  = 'Destroy Employee Position #';
            $trxid   = $id;
            $model   = 'employee position';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            if ($users > 0) {
                return Response::success("position with id:{$id} deleted");
            }else{
                throw new ApplicationException("user.failure_save_user");
            }

        } catch (Exception $e) {
            throw new ApplicationException("user.failure_save_user");
        }

    }

}
