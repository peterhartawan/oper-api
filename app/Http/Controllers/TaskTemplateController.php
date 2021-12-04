<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Services\Response;
use App\Services\Validate;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Constants\constant;
use App\Models\OrderTasks;
use App\Models\Order;
use DB;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\EventLog;

class TaskTemplateController extends Controller
{
     /**
     * Get Task Template list
     *
     * @param  [int] limit
     * @param  [int] page
     * @param  [int] identerprise // for superadmin to filter by identerprise
     * @return [json] Task Template object
     */
    public function index(Request $request)
    {
        $idvendor        = $request->query('idvendor');
        $is_dropdown     = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;
        $limit           = $request->query('limit');

        switch (auth()->guard('api')->user()->idrole) {
            case Constant::ROLE_SUPERADMIN:
            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
            case Constant::ROLE_VENDOR:
                if ($is_dropdown == Constant::OPTION_ENABLE) {
                    //hardcode karena FE blm ada dropdown search
                    $limit = 500;

                    $taskTemplate = TaskTemplate::select("task_template.task_template_id","task_template.task_template_name")
                                    ->where('status','=',Constant::STATUS_ACTIVE);
                }else{
                    $taskTemplate = TaskTemplate::with(["tasks","vendor"])
                                    ->where('status','=',Constant::STATUS_ACTIVE);
                }

                if($request->query('identerprise')){
                    $taskTemplate->where("client_enterprise_identerprise",$request->query('identerprise'));
                }
 
                if(!empty($idvendor)){
                    $taskTemplate->where("vendor_idvendor",$idvendor);
                }

                if(auth()->guard('api')->user()->idrole == Constant::ROLE_VENDOR){
                    $taskTemplate->where("vendor_idvendor",auth()->guard('api')->user()->vendor_idvendor);
                }

                return Response::success(
                    $taskTemplate->paginate($limit ?? Constant::LIMIT_PAGINATION)
                );

            break;
            case Constant::ROLE_ENTERPRISE:
            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
            case Constant::ROLE_DISPATCHER_ONDEMAND:
                
                return Response::success(
                    TaskTemplate::with("tasks")
                    ->where('status','!=',Constant::STATUS_DELETED)
                    ->where("client_enterprise_identerprise",auth()->guard('api')->user()->client_enterprise_identerprise)
                    ->paginate($limit ?? Constant::LIMIT_PAGINATION)
                );

                break;
            default:
                break;
        }

    }

    /**
     * Create Template detail
     *
     * @param  [string] template_name
     * @param  [string] template_description
     * @param  [array] tasks
     */
    public function store(Request $request)
    {
        $user   = auth()->guard('api')->user();

		Validate::request($request->all(), [
            'template_name' => 'required|min:3|max:45|string',
            'template_description' => 'nullable|max:500',
            'identerprise' => 'integer|nullable|exists:client_enterprise',
            'tasks' => 'array|required',
            'tasks.*.name' => 'required|min:3|max:45|string',
            'tasks.*.description' => 'nullable|max:500|string',
            'tasks.*.is_required' => 'nullable|boolean',
            'tasks.*.is_need_photo' => 'nullable|boolean',
            'tasks.*.is_need_inspector_validation' => 'nullable|boolean',
            'tasks.*.latitude' => 'nullable|string',
            'tasks.*.longitude' => 'nullable|string',
            'tasks.*.location_name' => 'nullable|string',
        ]);

        DB::beginTransaction();
        
        try {
            if ($user->idrole == constant::ROLE_VENDOR) {
                $taskTemplate = TaskTemplate::create([
                    'task_template_name' => $request->template_name,
                    'task_template_description' => $request->template_description,
                    'client_enterprise_identerprise' => $request->identerprise,
                    'vendor_idvendor' => auth()->guard('api')->user()->vendor_idvendor,
                    'created_by' => auth()->guard('api')->user()->id
                ]);
            } else {
                $taskTemplate = TaskTemplate::create([
                    'task_template_name' => $request->template_name,
                    'task_template_description' => $request->template_description,
                    'client_enterprise_identerprise' => $request->identerprise,
                    'created_by' => auth()->guard('api')->user()->id
                ]);
            }

           
            $tasks = [];
            foreach ($request->tasks as $index => $task){
                try {

                    $newTask = Task::create([
                        'task_template_id' => $taskTemplate->task_template_id,
                        'sequence' => $index+1,
                        'name' => $task["name"],
                        'description' => $task["description"] ?? null,
                        'is_required' => $task["is_required"] ?? 1,
                        'is_need_photo' => $task["is_need_photo"] ?? 0,
                        'is_need_inspector_validation' => $task["is_need_inspector_validation"] ?? 0,
                        'latitude' => $task["latitude"] ?? null,
                        'longitude' => $task["longitude"] ?? null,
                        'location_name' => $task["location_name"] ?? null,
                        'created_by' => auth()->guard('api')->user()->id
                    ]);
                    
                    $tasks[$index] = $newTask;
                }catch (Exception $e) {                   
                    throw new ApplicationException("tasktemplate.failure_save_task_template");
                }
            }

            $dataraw = '';
            $reason  = 'Create Task Template #';
            $trxid   = $taskTemplate->task_template_id;
            $model   = 'task template';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);
            
            DB::commit();
            $taskTemplate->tasks = $tasks;
            return Response::success($taskTemplate);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("tasktemplate.failure_save_tasktemplate");
        }
        
    }

    /**
     * Get Task Template detail
     *
     * @param  [int] id
     * @return [json] Task Template object
     */
    public function show($id)
    {
        $taskTemplate = TaskTemplate::where("task_template_id", $id)
            ->where('status', Constant::STATUS_ACTIVE)
            ->with("tasks")->first();

        if (empty($taskTemplate))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'task template','id' => $id]);

        return Response::success($taskTemplate);
    }

    /**
     * Update Template detail
     *
     * @param  [string] template_name
     * @param  [string] template_description
     * @param  [array] task
     */
    public function update(Request $request, $id)
    {
        $user   = auth()->guard('api')->user();

		Validate::request($request->all(), [
            'template_name' => 'required|min:3|max:45|string',
            'template_description' => 'nullable|max:500',
            'identerprise' => 'integer|nullable|exists:client_enterprise',
            'tasks' => 'array|required',
            'tasks.*.name' => 'required|min:3|max:45|string',
            'tasks.*.description' => 'nullable|max:500|string',
            'tasks.*.is_required' => 'nullable|boolean',
            'tasks.*.is_need_photo' => 'nullable|boolean',
            'tasks.*.is_need_inspector_validation' => 'nullable|boolean',
            'tasks.*.latitude' => 'nullable|string',
            'tasks.*.location_name' => 'nullable|string',
            'tasks.*.longitude' => 'nullable|string'
        ]);

        DB::beginTransaction();
        
        try {
            if ($user->idrole == constant::ROLE_VENDOR) {
                $taskTemplate = TaskTemplate::where('task_template_id', $id)
                                ->update([
                                    'task_template_name' => $request->template_name,
                                    'task_template_description' => $request->template_description,
                                    'client_enterprise_identerprise' => $request->identerprise,
                                    'vendor_idvendor' => auth()->guard('api')->user()->vendor_idvendor,
                                    'updated_by' => auth()->guard('api')->user()->id
                                ]);
            } else {
                $taskTemplate = TaskTemplate::where('task_template_id', $id)
                                ->update([
                                    'task_template_name' => $request->template_name,
                                    'task_template_description' => $request->template_description,
                                    'client_enterprise_identerprise' => $request->identerprise,
                                    'updated_by' => auth()->guard('api')->user()->id
                                ]);
            }
            if ($oldTasks = Task::where('task_template_id', $id)) {
                    $oldTasks->delete();
            }           

            foreach ($request->tasks as $index => $task){
                try {
                    $newTask = Task::create([
                        'task_template_id' => $id,  
                        'sequence' => $index+1,
                        'name' => $task["name"],
                        'description' => $task["description"] ?? null,
                        'is_required' => $task["is_required"] ?? 1,
                        'is_need_photo' => $task["is_need_photo"] ?? 0,
                        'is_need_inspector_validation' => $task["is_need_inspector_validation"] ?? 0,
                        'latitude' => $task["latitude"] ?? null,
                        'longitude' => $task["longitude"] ?? null,
                        'location_name' => $task["location_name"] ?? null,
                        'created_by' => auth()->guard('api')->user()->id
                    ]);
                }catch (Exception $e) {
                    throw new ApplicationException("tasktemplate.failure_save_task_template");
                }
            }
            
            $dataraw = '';
            $reason  = 'Update Task Template #';
            $trxid   = $id;
            $model   = 'task template';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            
            return Response::success("{$request->template_name} update success");
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("tasktemplate.failure_save_task_template");
        }
        
    }

    /**
     * Mark Task Template status as deleted
     *
     * @param  [int] id
     * @return [json] Task Template object
     */
    public function destroy($id)
    {
        try {
            $taskTemplate = TaskTemplate::where('task_template_id', $id)
            ->where('status',"!=",Constant::STATUS_DELETED)
            ->update([
                'status' => Constant::STATUS_DELETED
            ]);

            if ($taskTemplate > 0) {

                $dataraw = '';
                $reason  = 'Delete Task Template #';
                $trxid   = $id;
                $model   = 'task template';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);
                return Response::success("task template with id:{$id} deleted");
            }else{
                throw new ApplicationException("tasktemplate.failure_delete_task_template");
            }

        } catch (Exception $e) {
            throw new ApplicationException("tasktemplate.failure_delete_task_template");
        }
    }


    public function tasktemplatereporting(Request $request)
    {
        $daterange      = $request->daterange;
        $type_report    = $request->type_report;

        if (empty($daterange)) {
            throw new ApplicationException("errors.template_daterange");            
        }

        $user           = auth()->guard('api')->user();
        $from_date      = Carbon::parse(substr($daterange, 0, 10))->format('Y-m-d');
        $to_date        = Carbon::parse(substr($daterange, -10))->format('Y-m-d 23:59:59');
        
        switch ($user->idrole) {
            
            case Constant::ROLE_SUPERADMIN:
                $order = Order::select('order.*');
            break;

            case Constant::ROLE_VENDOR:
                $order = Order::select('order.*')
                    ->Join('users','order.created_by','=','users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
            break;

            case Constant::ROLE_ENTERPRISE:
                $order = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $id_client = DB::table('users')
                    ->select('id')
                    ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                    ->get();
                    
                $array = json_decode(json_encode($id_client), true);

                $order = Order::wherein('order.client_userid',$array);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $order = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            default:
            break;
        } 

            
        if( $type_report == 'driver'){
            $order = $order->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
        }else{
            $order = $order->where('order.order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);
        }

        $data_order = $order->whereBetween('order.booking_time', [$from_date, $to_date])
                      ->where('order.order_status', Constant::ORDER_COMPLETED);

        $id_template_order = $data_order->pluck('task_template_task_template_id');

        switch (auth()->guard('api')->user()->idrole) {
            case Constant::ROLE_SUPERADMIN:
            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
            case Constant::ROLE_VENDOR:

                $taskTemplate2 = TaskTemplate::select("task_template.task_template_id","task_template.task_template_name","task_template.task_template_description")
                                // ->where('status','!=',Constant::STATUS_DELETED)
                                ->whereIn('task_template_id',$id_template_order)
                                // ->where("vendor_idvendor",auth()->guard('api')->user()->vendor_idvendor)
                                ->get();

                $taskTemplate  = $taskTemplate2->map(function ($item, $key) use ($user,$from_date,$to_date,$type_report) {
                    switch ($user->idrole) {
            
                        case Constant::ROLE_SUPERADMIN:
                            $order2 = Order::select('order.*');
                        break;
            
                        case Constant::ROLE_VENDOR:
                            $order2 = Order::select('order.*')
                                ->Join('users','order.created_by','=','users.id')
                                ->where('users.vendor_idvendor', $user->vendor_idvendor);
                        break;
            
                        case Constant::ROLE_ENTERPRISE:
                            $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                        break;
            
                        case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                            $id_client = DB::table('users')
                                ->select('id')
                                ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                                ->where('users.vendor_idvendor', $user->vendor_idvendor)
                                ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                                ->get();
                                
                            $array = json_decode(json_encode($id_client), true);
            
                            $order2 = Order::wherein('order.client_userid',$array);
                        break;
            
                        case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                            $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                        break;
            
                        default:
                        break;
                    } 
                    
                    if( $type_report == 'driver'){
                        $order2 = $order2->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
                    }else{
                        $order2 = $order2->where('order.order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);
                    }

                    $item['order'] = $order2->whereBetween('order.booking_time', [$from_date, $to_date])
                                    ->where('task_template_task_template_id',$item->task_template_id)
                                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                                    ->get();
                    return $item;


                });

                return Response::success($taskTemplate);

            break;
            case Constant::ROLE_ENTERPRISE:
            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
            case Constant::ROLE_DISPATCHER_ONDEMAND:

                $taskTemplate2 = TaskTemplate::select("task_template.task_template_id","task_template.task_template_name")
                            // ->where('status','!=',Constant::STATUS_DELETED)
                            ->whereIn('task_template_id',$id_template_order)
                            // ->where("client_enterprise_identerprise",auth()->guard('api')->user()->client_enterprise_identerprise)
                            ->get();
                
                $taskTemplate  = $taskTemplate2->map(function ($item, $key) use ($user,$from_date,$to_date) {
                    switch ($user->idrole) {
            
                        case Constant::ROLE_SUPERADMIN:
                            $order2 = Order::select('order.*');
                        break;
            
                        case Constant::ROLE_VENDOR:
                            $order2 = Order::select('order.*')
                                ->Join('users','order.created_by','=','users.id')
                                ->where('users.vendor_idvendor', $user->vendor_idvendor);
                        break;
            
                        case Constant::ROLE_ENTERPRISE:
                            $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                        break;
            
                        case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                            $id_client = DB::table('users')
                                ->select('id')
                                ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                                ->where('users.vendor_idvendor', $user->vendor_idvendor)
                                ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                                ->get();
                                
                            $array = json_decode(json_encode($id_client), true);
            
                            $order2 = Order::wherein('order.client_userid',$array);
                        break;
            
                        case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                            $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                        break;
            
                        default:
                        break;
                    } 
                    
                    
                    $item['order'] = $order2->whereBetween('order.booking_time', [$from_date, $to_date])
                                    ->where('task_template_task_template_id',$item->task_template_id)
                                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                                    ->get();
                    return $item;
                });
                
                return Response::success($taskTemplate);

            break;
            default:
            break;
        }
       
    }

}
