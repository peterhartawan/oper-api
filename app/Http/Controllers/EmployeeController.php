<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use Carbon\Carbon;
use App\Services\Response;
use App\Models\Employee;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Task;
use App\Models\OrderTasks;
use App\Models\Attendance;
use App\Models\MobileNotification;
use App\Constants\Constant;
use App\Services\Validate;
use App\Models\TaskTemplate;
use App\PasswordReset;
use App\User;
use App\Notifications\AccountActivation;
use App\Notifications\UserNotification;
use Illuminate\Support\Facades\Storage;
use App\Notifications\OrderNotification;
use DB;
use App\Notifications\AssignDriversToClient;
use App\Http\Helpers\GlobalHelper;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\ChangeEmail;
use App\Notifications\EmailActivation;
use App\Notifications\EmailActivationRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReportingEmployee;
use App\Http\Helpers\Paginator;
use App\Http\Helpers\EventLog;

class EmployeeController extends Controller
{

    /**
     * Get driver active
     *
     * @param [string] q
     * @param [string] id
     * @return [json] Employee object
     */
    public function index(Request $request)
    {
        $keyword_search     = $request->query('q');
        $status             = $request->query('status');
        $user               = auth()->guard('api')->user();
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE;
        $orderBy            = $request->query('orderBy');

        $employee = Employee::where('users.idrole', Constant::ROLE_EMPLOYEE)
            ->join('users', 'employee.users_id', '=', 'users.id');

        if (!empty($status)) {
            $employee = $employee->where("users.status", $status);
        } else {
            $employee = $employee->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_SUSPENDED]);
        }

        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $employee = $employee->select('users.id', 'users.name');

            if (!empty($keyword_search))
                $employee = $employee->where("users.name", "like", "%" . $keyword_search . "%");

            // //employee checkin
            // pending
            // $employee = $employee->leftJoin('attendance','attendance.users_id','=','users.id')
            //             ->where("clock_in",">=",Carbon::today()->toDateString())
            //             ->whereNull("attendance.clock_out");

        } else {
            $employee = $employee->with(["user", "employee_position"]);

            if (!empty($keyword_search))
                $employee = $employee->where(function ($query) use ($keyword_search) {
                    $query->where('users.name', 'like', '%' . $keyword_search . '%');
                });
        }
        switch ($user->idrole) {
            case constant::ROLE_VENDOR:
                $employee->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
                break;
        }
        //OrderBy
        $employee->orderBy('idemployee', $orderBy ?? 'DESC');
        $employee = $employee->get();
        array_walk($employee, function (&$v, $k) {
            foreach ($v as $item) {
                if (!empty($item->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                }
                if (!empty($item->profil_picture_2)) {
                    $item->profil_picture_2 = env('BASE_API') . Storage::url($item->profil_picture_2);
                }
            }
        });

        $page = $request->page ? $request->page : 1;
        $perPage = $request->query('limit') ?? Constant::LIMIT_PAGINATION;
        $all_employee = collect($employee);
        $employee_new = new Paginator($all_employee->forPage($page, $perPage), $all_employee->count(), $perPage, $page, [
            'path' => url("attendance/reporting?type=driver")
        ]);
        return Response::success($employee_new);

        // return Response::success($employee->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
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
     * @param  [string] attendance_latitude
     * @param  [string] attendance_longitude
     * @return [json] Employee object
     */
    public function store(Request $request)
    {

        Validate::request($request->all(), [
            'nik' => 'required|min:16|max:16|string',
            'idemployee_position' => 'required|string',
            'birthdate' => 'required',
            'gender' => 'required|string',
            'address' => 'required|max:500|string',
            'name' => 'required|min:3|max:45|string',
            'email' => 'required|min:10|max:80|email|unique:users,email',
            'phonenumber' => 'required|min:10|max:45|string|unique:users,phonenumber',
            'attendance_latitude'   => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'attendance_longitude'  => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:' . Constant::MAX_IMAGE_SIZE,
            'photo_2' => 'nullable|image|mimes:jpeg,png,jpg|max:' . Constant::MAX_IMAGE_SIZE,
        ]);

        DB::beginTransaction();
        try {
            $idvendor = auth()->guard('api')->user()->vendor_idvendor;
            $pass     = rand(12345678, 45678910);

            if ($request->hasfile('photo')) {
                $path = Storage::putFile("/public/images/users", $request->file('photo'));
            } else {
                $path = '';
            }

            if ($request->hasfile('photo_2')) {
                $path_2 = Storage::putFile("/public/images/users", $request->file('photo_2'));
            } else {
                $path_2 = '';
            }

            $user = User::create([
                'name'  => $request->name,
                'email' => $request->email,
                'password'  => bcrypt($pass),
                'phonenumber'   => $request->phonenumber,
                'idrole'    => Constant::ROLE_EMPLOYEE,
                'vendor_idvendor'   => $idvendor,
                'profile_picture'  => $path,
                'profil_picture_2'  => $path_2,
                'status'    => constant::STATUS_ACTIVE
            ]);

            $Employee = Employee::create([
                'users_id' => $user->id,
                'nik' => $request->nik,
                'idemployee_position' => $request->idemployee_position,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'address' => $request->address,
                'attendance_latitude' => $request->attendance_latitude,
                'attendance_longitude' => $request->attendance_longitude,
                'created_by' => $request->user()->id
            ]);

            $Employee    = Employee::with(["user", "employee_position"])
                ->where('employee.users_id', $user->id)
                ->first();


            $dataraw = '';
            $reason  = 'Create Employee #';
            $trxid   = $Employee->idemployee;
            $model   = 'employee';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            DB::commit();
            if ($user && $Employee)
                $user->notify(
                    new UserNotification("Your Pin Employee {$pass}")
                );
            return Response::success($Employee);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("employee.failure_save_employee");
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
        $Employee = Employee::select('employee.*', 'users.*')
            ->with(["user", "employee_position"])
            ->where('employee.idemployee', $id)
            ->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_INACTIVE, Constant::STATUS_SUSPENDED])
            ->join('users', 'employee.users_id', '=', 'users.id')
            ->first();

        if (empty($Employee)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'idemployee', 'id' => $id]);
        }

        // change image url to laravel path
        if (!empty($Employee->profile_picture)) {
            $Employee->profile_picture = Storage::url($Employee->profile_picture);
            $Employee->profile_picture = env('BASE_API') . $Employee->profile_picture;
        }
        // change image url to laravel path
        if (!empty($Employee->profil_picture_2)) {
            $Employee->profil_picture_2 = Storage::url($Employee->profil_picture_2);
            $Employee->profil_picture_2 = env('BASE_API') . $Employee->profil_picture_2;
        }

        return Response::success($Employee);
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
            'nik' => 'required|min:16|max:16|string',
            'idemployee_position' => 'required|string',
            'birthdate' => 'required',
            'gender' => 'required|string',
            'address' => 'required|max:500|string',
            'name' => 'required|min:3|max:45|string',
            'phonenumber'   => 'required|min:10|max:45|string|unique:users,phonenumber,' . $id,
            'email'         => 'required|min:10|max:80|email',
            'attendance_latitude'   => ['nullable', 'regex:/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/'],
            'attendance_longitude'  => ['nullable', 'regex:/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/'],
            'photo'         => 'nullable|image|mimes:jpeg,png,jpg|max:' . Constant::MAX_IMAGE_SIZE,
            'photo_2'         => 'nullable|image|mimes:jpeg,png,jpg|max:' . Constant::MAX_IMAGE_SIZE
        ]);

        DB::beginTransaction();
        try {
            $new_email      = $request->email;
            $Employee       = Employee::where('users_id', $id)->first();
            $Users          = User::where('id', $id)->first();

            if ($Employee == null)
                throw new ApplicationException("errors.entity_not_found", ['entity' => 'Driver', 'id' => $id]);


            if ($new_email != $Users->email && $new_email != "") {
                $this->updateEmailUser($request, $id);
            }

            $update   = $Employee->update([
                'nik' => $request->nik,
                'idemployee_position' => $request->idemployee_position,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'address' => $request->address,
                'attendance_latitude' => $request->attendance_latitude,
                'attendance_longitude' => $request->attendance_longitude,
                'updated_by' => $request->user()->id
            ]);

            $user       = $Users->update([
                'name'              => $request->name,
                'phonenumber'       => $request->phonenumber
            ]);
            if ($request->hasfile('photo')) {
                $path = Storage::putFile("/public/images/users", $request->file('photo'));
                $user       = $Users->update([
                    'profile_picture'   => $path
                ]);
            }

            if ($request->hasfile('photo_2')) {
                $path_2     = Storage::putFile("/public/images/users", $request->file('photo_2'));
                $user2      = $Users->update([
                    'profil_picture_2'   => $path_2
                ]);
            }

            $data_employee    = Employee::with(["user", "employee_position"])
                ->where('employee.users_id', $id)
                ->join('users', 'employee.users_id', '=', 'users.id')
                ->first();

            $dataraw = '';
            $reason  = 'Update Employee #';
            $trxid   = $data_employee->idemployee;
            $model   = 'employee';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            DB::commit();
            return Response::success($data_employee);
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
    public function destroy(Request $request, $id)
    {
        $user = User::where('id', $id)->first();

        if ($user->status == Constant::STATUS_SUSPENDED) {
            $jum_transaksi   = Order::where('employee_userid', $id)
                ->count();

            if ($jum_transaksi > 0) {
                throw new ApplicationException("errors.cannot_delete_account");
            }

            $user = $user->delete();
            Employee::where('users_id', $id)->delete();

            $dataraw = '';
            $reason  = 'Delete Employee user_id #';
            $trxid   = $id;
            $model   = 'employee';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            return Response::success(['id' => $id]);
        } else {
            throw new ApplicationException("user.failure_delete_user", ['id' => $id]);
        }
    }

    /**
     * Dispatch Order to Driver
     */
    public function assign(Request $request)
    {
        Validate::request($request->all(), [
            'task_template_id'  => 'int|required|exists:task_template',
            'booking_time'      => "required|date_format:" . Constant::DATE_FORMAT,
            'message'          => "nullable|string",
            'employee_userid'  => "required|integer|exists:users,id",

        ]);

        //ditutup dulu
        // $checkAttendance = Attendance::where('users_id', $request->employee_userid)
        //                 ->where("clock_in",">=",Carbon::today()->toDateString())
        //                 ->whereNull("attendance.clock_out")
        //                 ->first();

        // if(empty($checkAttendance)){
        //     throw new ApplicationException("attendance.failure_attendance_task_clockin");
        // } 

        $userId = auth()->guard('api')->user()->id;
        $idRole = auth()->guard('api')->user()->idrole;
        $idVendor = auth()->guard('api')->user()->vendor_idvendor;
        $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;
        $client_userid = null;

        //Get employee
        $employee = Employee::where("users_id", $request->employee_userid)
            ->leftJoin('users', 'employee.users_id', '=', 'users.id')
            ->first();

        if (empty($employee))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'Employee', 'id' => $request->employee_userid]);

        //validasi vendor employee sama dengan vendor login
        if ($idVendor != $employee->vendor_idvendor) {
            throw new ApplicationException("employee.employee_vendor_not_same");
        }

        // if ($employee->is_on_task == Constant::BOOLEAN_TRUE)
        //     throw new ApplicationException("employee.failure_assign_order_employee");

        $orderType = Constant::ORDER_TYPE_EMPLOYEE;

        $trxId = GlobalHelper::generateTrxId();
        if (empty($trxId)) {
            throw new ApplicationException("orders.invalid_creating_trx_id");
        }

        DB::beginTransaction();
        try {
            $order = Order::create([
                'trx_id' => $trxId,
                'task_template_task_template_id'  => $request->task_template_id,
                'user_fullname'  =>  "",
                'user_phonenumber'  => "",
                'booking_time'      => $request->booking_time,
                'message'  => $request->message ?? "",
                'order_type_idorder_type' => $orderType,
                'status'        => Constant::STATUS_ACTIVE,
                'created_by'    => auth()->guard('api')->user()->id,
                'employee_userid' => $request->employee_userid,
                'order_status' => Constant::ORDER_INPROGRESS,
                'dispatch_at' => Carbon::now()
            ]);

            $tasks = Task::where("task_template_id", $request->task_template_id)->get();
            foreach ($tasks as $key => $task) {
                try {

                    OrderTasks::create([
                        'task_idtask'   => $task->idtask,
                        'order_idorder' => $order->idorder,
                        'order_task_status' => Constant::ORDER_TASK_NOT_STARTED,
                        'status' => Constant::STATUS_ACTIVE,
                        'sequence' => $task->sequence,
                        'name' => $task->name,
                        'description' => $task->description,
                        'is_required' => $task->is_required,
                        'is_need_photo' => $task->is_need_photo,
                        'is_need_inspector_validation' => $task->is_need_inspector_validation,
                        'latitude' => $task->latitude,
                        'longitude' => $task->longitude,
                        'location_name' => $task->location_name,
                        'created_by' => auth()->guard('api')->user()->id
                    ]);
                } catch (Exception $e) {
                    DB::rollBack();
                    throw new ApplicationException("orders.failure_create_order");
                }
            }
            //
            $updateTaskpertama = OrderTasks::where("order_idorder", $order->idorder)
                ->where("sequence", 1)
                ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

            // $emp = Employee::where("users_id",$request->employee_userid)
            //     ->update(["is_on_task" => true]);


            $tokenMobile = MobileNotification::where("user_id", $request->employee_userid)
                ->first();

            $fcmRegIds = array();

            if (!empty($tokenMobile)) {
                array_push($fcmRegIds, $tokenMobile->token);
            }

            $data_order = $order->first();
            if (!empty($fcmRegIds)) {
                $title           = "New Tasks #{$trxId}";
                $messagebody     = "You have new tasks!";
                $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody);
                $returnsendorder = Notification::sendNotification($getGenNotif);


                if ($returnsendorder == false) {
                    Log::critical("failed send Notification  : {$request->employee_userid} ");
                }
            } else {
                Log::critical("failed send Notification  : {$request->employee_userid} ");
            }

            //send email to employee
            $taskTemplate           = TaskTemplate::where("task_template_id", $request->task_template_id)->first();

            //send email to driver
            $user_driver = User::where('id', $request->employee_userid)
                ->first();
            $orderan2     =  [
                'greeting' => 'Your have new task',
                'line' => [
                    'Transaction ID' => $order->trx_id,
                    'Task' => $taskTemplate->task_template_name,
                ],
            ];


            $array = array(
                'order_idorder' => $data_order->idorder
            );
            $dataraw = json_encode($array);
            $reason  = 'Assign Task Employee#';
            $trxid   = $trxId;
            $model   = 'Task';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            DB::commit();
            if ($user_driver) {
                $user_driver->notify(new OrderNotification($orderan2));
            }
            $response = Order::find($order->idorder);
            return Response::success($response);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("orders.failure_create_order");
        }
    }

    public function showByTrxId($trxId)
    {
        $user = auth()->guard('api')->user();
        $order = Order::with(["order_tasks"])->where('trx_id', $trxId);

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
                break;

            case Constant::ROLE_VENDOR:
                $order->leftJoin('users', 'order.created_by', 'users.vendor_idvendor')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
                break;

            case Constant::ROLE_EMPLOYEE:
                $order->where('order.employee_userid', $user->id);
                break;
        }

        return Response::success($order->first());
    }

    /**
     * Get list inprogress tasks
     *
     * @return [json] list tasks object
     */
    public function inprogress(Request $request)
    {
        return $this->getListTasksByStatus($request, Constant::ORDER_INPROGRESS);
    }
    /**
     * Get list complete tasks
     *
     * @return [json] list tasks object
     */
    public function complete(Request $request)
    {
        return $this->getListTasksByStatus($request, Constant::ORDER_COMPLETED);
    }

    public function showInprogress($id)
    {
        return $this->getDetailTaskByStatus($id, Constant::ORDER_INPROGRESS);
    }

    public function showComplete($id)
    {
        return $this->getDetailTaskByStatus($id, Constant::ORDER_COMPLETED);
    }

    private function getListTasksByStatus($request, $order_status)
    {
        $order = new Order;
        $users = new User;
        $AgoDate = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');  // returns 2016-02-03
        $NowDate = \Carbon\Carbon::now()->format('Y-m-d');


        $user = auth()->guard('api')->user();
        $month              = $request->query('month');
        $export             = $request->query('export');
        $week               = $request->query('week');
        $vendor             = $request->query('idvendor');
        $trxId              = $request->query('trx_id');
        $from               = $request->query('from');
        $to                 = $request->query('to');

        $order = Order::with(["employee"])->select('order.*')->whereNotNull('employee_userid');

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
                $order
                    ->join('task_template', 'order.task_template_task_template_id', 'task_template.task_template_id')
                    ->where('order_status', $order_status)
                    ->where('order.order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);

                if (!empty($vendor)) {
                    $order = $order->Join('users', 'order.created_by', '=', 'users.id')
                        ->where('users.vendor_idvendor', $vendor);
                }
                break;

            case Constant::ROLE_VENDOR:
                $order->Join('users', 'order.created_by', '=', 'users.id')
                    ->join('task_template', 'order.task_template_task_template_id', 'task_template.task_template_id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE)
                    ->where('order.order_status', $order_status);
                break;


            default:
                throw new ApplicationException("errors.access_denied");
                break;
        }

        if (!empty($trxId)) {
            $order->where('trx_id', $trxId);
        }

        if (!empty($month)) {
            $order = $order->whereMonth('order.booking_time', $month);
        }

        if (!empty($from) && !empty($to)) {
            $from_date  = \Carbon\Carbon::createFromFormat("!Y-m-d", $from);
            $to_date    = \Carbon\Carbon::createFromFormat("!Y-m-d", $to)->addDays(1);
            $order = $order->whereBetween('order.booking_time', [$from_date, $to_date]);
        }

        $order->leftjoin('users as detail_employee', 'detail_employee.id', '=', 'order.employee_userid')
            ->select(DB::raw("`order`.*,`task_template`.`task_template_name`,`detail_employee`.`profile_picture`, IF(order.order_status = 1, 'Open', IF(order.order_status = 2, 'In Progress', IF(order.order_status = 3, 'Completed', 'Unknown'))) as status_text"));

        // return $order->toSql();
        $order = $order->get();
        array_walk($order, function (&$v, $k) {
            foreach ($v as $item) {
                if (!empty($item->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                }
                if (!empty($item->profil_picture_2)) {
                    $item->profil_picture_2 = env('BASE_API') . Storage::url($item->profil_picture_2);
                }
            }
        });

        $page = $request->page ? $request->page : 1;
        $perPage = $request->query('limit') ?? Constant::LIMIT_PAGINATION;
        $all_order = collect($order);
        $order_new = new Paginator($all_order->forPage($page, $perPage), $all_order->count(), $perPage, $page, [
            'path' => url("order/open")
        ]);

        return Response::success($order_new);

        // return Response::success($order->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));


    }

    private function getDetailTaskByStatus($id, $order_status)
    {
        $user = auth()->guard('api')->user();
        $order = Order::with(["employee", "order_tasks"])->select('order.*')->where('idorder', $id)->whereNotNull('employee_userid');

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
                $order->where('order_status', $order_status);
                break;

            case Constant::ROLE_VENDOR:
                $order->leftJoin('users', 'order.created_by', 'users.id')
                    ->where('order.order_status', $order_status)
                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
                break;

            default:
                throw new ApplicationException("errors.access_denied");
                break;
        }
        $detail_order = $order->first();

        $no = 1;
        array_walk($detail_order->order_tasks, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->attachment_url)) {
                    $item->attachment_url = env('BASE_API') . Storage::url($item->attachment_url);
                }
            }
            $no++;
        });

        if (!empty($detail_order->employee->user->profile_picture)) {
            $pertama = Storage::url($detail_order->employee->user->profile_picture);
            $detail_order->employee->user->profile_picture = env('BASE_API') . $pertama;
        }
        if (!empty($detail_order->employee->user->profile_picture_2)) {
            $pertama = Storage::url($detail_order->employee->user->profile_picture_2);
            $detail_order->employee->user->profile_picture_2 = env('BASE_API') . $pertama;
        }


        return Response::success($detail_order);
    }
    private function updateEmailUser(Request $request, $id)
    {

        $users            = User::findOrFail($id);
        $status           = Constant::OPTION_DISABLE;
        $role_login       = auth()->guard('api')->user()->idrole;
        $role_update      = $users->idrole;
        $new_email        = $request->email;

        $dispatcherRole = [
            Constant::ROLE_ENTERPRISE,
            Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER,
            Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS,
            Constant::ROLE_DISPATCHER_ONDEMAND
        ];

        // if( $role_update == Constant::ROLE_SUPERADMIN ){
        //     $linkurl = env('URL_ADMIN_OPER');
        // }elseif( $role_update == Constant::ROLE_VENDOR || $role_update == Constant::ROLE_DRIVER || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS 
        // || $role_update == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $role_update == Constant::ROLE_DISPATCHER_ONDEMAND ){
        //     $linkurl = env('URL_VENDOR');
        // }elseif( $role_update == Constant::ROLE_ENTERPRISE  ){
        //     $ClientEnterprises = ClientEnterprise::where('identerprise',$users->client_enterprise_identerprise)->first();
        //     $linkurl = $ClientEnterprises->site_url;
        // }


        if ($users) {

            if ($role_login == Constant::ROLE_SUPERADMIN) {
                $status   = Constant::OPTION_ENABLE;
            } elseif ($role_login == Constant::ROLE_VENDOR) {
                if ($role_update == Constant::ROLE_DRIVER) {
                    $status   = Constant::OPTION_ENABLE;
                }
                if ($role_update == Constant::ROLE_EMPLOYEE) {
                    $status   = Constant::OPTION_ENABLE;
                }
                if (in_array($role_update, $dispatcherRole)) {
                    $status     = Constant::OPTION_ENABLE;
                }
                if ($role_update == Constant::ROLE_SUPERADMIN) {
                    $status     = Constant::OPTION_DISABLE;
                }
                if ($role_update == Constant::ROLE_VENDOR) {
                    $status     = Constant::OPTION_DISABLE;
                }
            } elseif (in_array($role_login, $dispatcherRole)) {
                $status         = Constant::OPTION_DISABLE;
            } elseif ($role_login == Constant::ROLE_DRIVER) {
                $status         = Constant::OPTION_DISABLE;
            } else {
                $status = Constant::OPTION_DISABLE;
            }

            if ($status == Constant::OPTION_ENABLE) {
                if ($new_email != $users->email && $new_email != "") {
                    $users->status = Constant::STATUS_INACTIVE;
                    $users->update();

                    $checkEmail = User::where('email', $new_email)->first();
                    if ($checkEmail) {
                        throw new ApplicationException("change_email.already_exist", ['email' => $new_email]);
                    }

                    //update email employee , urlnya di set ke vendor
                    $linkurl = env('URL_VENDOR');

                    $changeEmail = ChangeEmail::updateOrCreate(
                        ['old_email' => $users->email],
                        [
                            'old_email' => $users->email,
                            'new_email' => $new_email,
                            'token'     => str_random(60)
                        ]
                    );
                    if ($users && $changeEmail) {
                        $users->notify(
                            new EmailActivation($changeEmail->new_email)
                        );

                        \Notification::route('mail', $changeEmail->new_email)
                            ->notify(new EmailActivationRequest($changeEmail->token, $linkurl));
                    }

                    $array = array(
                        'users_id' => $users->id
                    );
                    $dataraw = json_encode($array);
                    $reason  = 'Update Email User';
                    $trxid   = $new_email;
                    $model   = 'employee';
                    EventLog::insertLog($trxid, $reason, $dataraw, $model);

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
     * id user employee 
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
                $Employee = Employee::select('employee.*', 'users.*')
                    ->where('users.id', $request->id_user)
                    ->join('users', 'employee.users_id', '=', 'users.id')
                    ->first();
                break;

            case Constant::ROLE_VENDOR:
                $Employee = Employee::select('employee.*', 'users.*')
                    ->where('users.id', $request->id_user)
                    ->join('users', 'employee.users_id', '=', 'users.id')
                    ->Where('users.vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                    ->first();
                break;

            default:
                break;
        }

        if (empty($Employee)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'id user employee', 'id' => $request->id_user]);
        }

        $pass = rand(12345678, 45678910);

        $user2 = User::where('id', $request->id_user)
            ->update([
                'password'  => bcrypt($pass),
                'created_by' => $request->user()->id
            ]);

        $user = User::where('id', $request->id_user)->first();
        if ($user)
            $user->notify(
                new UserNotification("Your Pin Employee {$pass}")
            );

        return Response::success($user);
    }


    /**
     * Dispatch Order to Driver
     */
    public function cancelorder(Request $request)
    {
        Validate::request($request->all(), [
            'idorder'        => "required|integer|exists:order",
            'reason_cancel'  => "required|string"
        ]);

        //cek yang login dispatcher bukan
        $user       = auth()->guard('api')->user();
        if (!in_array($user->idrole, [Constant::ROLE_VENDOR]))
            throw new ApplicationException('errors.unauthorized');

        //cek apakah order sudah di assign
        $order      = Order::where('idorder', $request->idorder)
            ->where('employee_userid', '!=', null);

        $data_order = $order->first();

        if (empty($data_order)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'idorder', 'id' => $request->idorder]);
        }

        if ($data_order->order_status != Constant::ORDER_INPROGRESS)
            throw new ApplicationException("orders.failure_cancel_order");

        //cek apakah order task sudah di proses atau belum
        $assign_order = OrderTasks::where("order_idorder", $request->idorder)
            ->where(function ($q) {
                $q->where('order_task_status', Constant::ORDER_TASK_COMPLETED)
                    ->orWhere('order_task_status', Constant::ORDER_TASK_SKIPPED);
            })
            ->get();
        if ($assign_order->count() > 0) {
            throw new ApplicationException("orders.failure_cancel_order");
        }

        //update status order
        $order->update(
            [
                'order_status' => Constant::ORDER_CANCELED,
                'reason_cancel' => $request->reason_cancel,
                'updated_by'   => $request->user()->id,
            ]
        );

        // $employee             = Employee::where("users_id",$data_order->employee_userid)
        //                         ->update(["is_on_task" => false]);

        $tokenMobile          = MobileNotification::where("user_id", $data_order->driver_userid)
            ->first();
        $fcmRegIds            = array();

        if (!empty($tokenMobile))
            array_push($fcmRegIds, $tokenMobile->token);

        if ($fcmRegIds) {
            $title           = "Cancel Order #{$data_order->trx_id}";
            $messagebody     = "Your order was canceled";
            $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody);
            $returnsendorder = Notification::sendNotification($getGenNotif);
            if ($returnsendorder == false) {
                Log::critical("failed send Notification Cancel Order : {$data_order->driver_userid} ");
            }
        } else {
            Log::critical("failed send Notification Cancel Order  : {$data_order->driver_userid} ");
        }

        $array = array(
            'order_idorder' => $data_order->idorder
        );
        $dataraw = json_encode($array);
        $reason  = 'Cancel Task #';
        $trxid   = $data_order->trx_id;
        $model   = 'task';
        EventLog::insertLog($trxid, $reason, $dataraw, $model);

        return Response::success($order->first());
    }

    public function orderReporting(Request $request)
    {
        $idtemplate   = $request->idtemplate;
        $daterange    = $request->daterange;

        if (empty($idtemplate) || empty($daterange)) {
            throw new ApplicationException("errors.template_daterange");
        }

        $file_name    = "Reportingemployee" . $idtemplate . "-" . $daterange . ".xlsx";
        Excel::store(new ReportingEmployee($idtemplate, $daterange), '/public/file/' . $file_name);
        $fileexport = Storage::url('file/' . $file_name);
        return Response::success(["file export" => url($fileexport)]);
    }
    public function totalAccount()
    {
        $user = auth()->guard('api')->user();

        $employee           = DB::table('employee')->select(DB::raw('count(*) as total_employee'))
            ->join('users', 'employee.users_id', '=', 'users.id')
            ->where('users.idrole', Constant::ROLE_EMPLOYEE)
            ->whereIn('users.status', [Constant::STATUS_ACTIVE, Constant::STATUS_SUSPENDED]);

        $employeeactive     = DB::table('employee')->select(DB::raw('count(*) as total_employee_active'))
            ->join('users', 'employee.users_id', '=', 'users.id')
            ->where('users.idrole', Constant::ROLE_EMPLOYEE)
            ->where('users.status', Constant::STATUS_ACTIVE);
        $employeesuspend     = DB::table('employee')->select(DB::raw('count(*) as total_employee_suspend'))
            ->join('users', 'employee.users_id', '=', 'users.id')
            ->where('users.idrole', Constant::ROLE_EMPLOYEE)
            ->where('users.status', Constant::STATUS_SUSPENDED);
        switch ($user->idrole) {


            case Constant::ROLE_VENDOR:
                $employee         = $employee->where("users.vendor_idvendor", $user->vendor_idvendor);
                $employeeactive   = $employeeactive->where("users.vendor_idvendor", $user->vendor_idvendor);
                $employeesuspend  = $employeesuspend->where("users.vendor_idvendor", $user->vendor_idvendor);
                break;
        }
        $employee         = $employee->first();
        $employeeactive   = $employeeactive->first();
        $employeesuspend  = $employeesuspend->first();

        $report                         = new \stdClass();
        $report->total_driver           = $employee->total_employee;
        $report->total_driver_active    = $employeeactive->total_employee_active;
        $report->total_driver_suspend   = $employeesuspend->total_employee_suspend;

        return Response::success($report);
    }

    public function totalorderweek(Request $request)
    {
        return $this->getTotalOrderByMonth($request, "week");
    }

    public function totalordermonth(Request $request)
    {
        return $this->getTotalOrderByMonth($request, "month");
    }

    public function totalordertoday(Request $request)
    {
        return $this->getTotalOrderByMonth($request, "today");
    }


    private function getTotalOrderByMonth($request, $order_date)
    {

        $order      = new Order;
        $users      = new User;
        $AgoDate    = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');  // returns 2016-02-03
        $NowDate    = \Carbon\Carbon::now()->format('Y-m-d');
        $month      = \Carbon\Carbon::now()->format('m');;
        $user       = auth()->guard('api')->user();
        $vendor     = $request->query('idvendor');

        switch ($user->idrole) {

            case Constant::ROLE_SUPERADMIN:
                $order_open       = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
                $order_success    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                $order_open_list       = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                break;

            case Constant::ROLE_VENDOR:
                $order_open = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);


                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                $order_success    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                $order_open_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);


                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                break;

            case Constant::ROLE_ENTERPRISE:
                $order_open       = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_open_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $id_client = DB::table('users')
                    ->select('id')
                    ->leftjoin('client_enterprise', 'client_enterprise.identerprise', '=', 'users.client_enterprise_identerprise')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                    ->get();

                $array = json_decode(json_encode($id_client), true);

                $order_open        = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_inprogress  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_success  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_open_list        = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_inprogress_list  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_success_list  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:

                $order_open         = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);


                $order_inprogress   = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success      = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_open_list         = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);


                $order_inprogress_list   = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success_list      = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->where('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);


                break;

            case    Constant::ROLE_DISPATCHER_ONDEMAND:
                break;

            default:
                break;
        }


        if ($order_date == "month") {

            $order_open         = $order_open->whereMonth('order.booking_time', $month);
            $order_inprogress   = $order_inprogress->whereMonth('order.booking_time', $month);
            $order_success      = $order_success->whereMonth('order.booking_time', $month);


            $order_open_list         = $order_open_list->whereMonth('order.booking_time', $month);
            $order_inprogress_list   = $order_inprogress_list->whereMonth('order.booking_time', $month);
            $order_success_list      = $order_success_list->whereMonth('order.booking_time', $month);
        } else if ($order_date == "week") {

            $order_open         = $order_open->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_inprogress   = $order_inprogress->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_success      = $order_success->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);

            $order_open_list         = $order_open_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_inprogress_list   = $order_inprogress_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_success_list      = $order_success_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
        } else if ($order_date == "today") {

            $order_open         = $order_open->whereDate('order.booking_time', '=', $NowDate);
            $order_inprogress   = $order_inprogress->whereDate('order.booking_time', '=', $NowDate);
            $order_success      = $order_success->whereDate('order.booking_time', '=', $NowDate);

            $order_open_list         = $order_open_list->whereDate('order.booking_time', '=', $NowDate);
            $order_inprogress_list   = $order_inprogress_list->whereDate('order.booking_time', '=', $NowDate);
            $order_success_list      = $order_success_list->whereDate('order.booking_time', '=', $NowDate);
        }

        $order_open_list        = $order_open_list->selectRaw('order.idorder,order.booking_time as tgl_buat')
            ->get()
            ->groupBy(
                function ($date) {
                    return Carbon::parse($date->tgl_buat)->format('Y-m-d'); // grouping by day
                }
            );;

        $order_inprogress_list  = $order_inprogress_list->selectRaw('order.idorder,order.booking_time as tgl_buat')
            ->get()
            ->groupBy(
                function ($date) {
                    return Carbon::parse($date->tgl_buat)->format('Y-m-d'); // grouping by day
                }
            );;

        $order_success_list    = $order_success_list->selectRaw('order.idorder,order.booking_time as tgl_buat')
            ->get()
            ->groupBy(
                function ($date) {
                    return Carbon::parse($date->tgl_buat)->format('Y-m-d'); // grouping by day
                }
            );;

        $label_open = [];
        $series_open = [];
        $label_inprogress = [];
        $series_inprogress = [];
        $label_success = [];
        $series_success = [];


        if ($order_date == "month") {
            $akhir  = Carbon::now();
            $awal   = Carbon::now()->startOfMonth();
        } else if ($order_date == "week") {
            $akhir  = Carbon::now();
            $awal   = Carbon::now()->subDays(6);
        } else if ($order_date == "today") {
            $awal  = Carbon::now();
            $akhir  = Carbon::now();
        }
        $tampung = 0;
        $tampung2 = 0;
        $tampung3 = 0;

        while (strtotime($awal) <= strtotime($akhir)) {

            if ($order_date == "month") {
                $label_open[]       = date("d", strtotime($awal));
                $label_inprogress[] = date("d", strtotime($awal));
                $label_success[]    = date("d", strtotime($awal));
            } else {
                $label_open[]       = date("Y-m-d", strtotime($awal));
                $label_inprogress[] = date("Y-m-d", strtotime($awal));
                $label_success[]    = date("Y-m-d", strtotime($awal));
            }

            $tgl_sekarang       = date("Y-m-d", strtotime($awal));

            foreach ($order_open_list as $key => $value) {
                $tgl = date("Y-m-d", strtotime($key));
                if ($tgl_sekarang == $tgl) {
                    $tampung = count($value);
                }
            }
            $series_open[] = $tampung;

            foreach ($order_inprogress_list as $key => $value) {
                $tgl = date("Y-m-d", strtotime($key));
                if ($tgl_sekarang == $tgl) {
                    $tampung2 = count($value);
                }
            }
            $series_inprogress[] = $tampung2;

            foreach ($order_success_list as $key => $value) {
                $tgl = date("Y-m-d", strtotime($key));
                if ($tgl_sekarang == $tgl) {
                    $tampung3 = count($value);
                }
            }
            $series_success[] = $tampung3;

            $awal = date("Y-m-d", strtotime("+1 day", strtotime($awal)));
            $tampung = 0;
            $tampung2 = 0;
            $tampung3 = 0;
        }


        $orderObjopen = new \stdClass();
        $orderObjopen->labels = $label_open;
        $orderObjopen->series = $series_open;

        $orderObjinprogress = new \stdClass();
        $orderObjinprogress->labels = $label_inprogress;
        $orderObjinprogress->series = $series_inprogress;


        $orderObjsuccess = new \stdClass();
        $orderObjsuccess->labels = $label_success;
        $orderObjsuccess->series = $series_success;

        $report                         = new \stdClass();
        // $report->order_open             = $order_open->count();
        $report->order_inprogress       = $order_inprogress->count();
        $report->order_complete         = $order_success->count();


        $grafik                 = new \stdClass();
        $grafik->total_order    = $report;
        $grafik->grafik         = new \stdClass();
        // $grafik->grafik->open          = $orderObjopen;
        $grafik->grafik->inprogress    = $orderObjinprogress;
        $grafik->grafik->complete    = $orderObjsuccess;



        return Response::success($grafik);
    }
}
