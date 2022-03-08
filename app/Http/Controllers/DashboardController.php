<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use App\Models\Role;
use App\Models\Vendor;
use App\Models\Order;
use Validator;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use DB;

class DashboardController extends Controller
{
    /**
     * Get dashbord information
     *
     * @TODO
     * - Enhance query for better performance
     * - Cache Result
     */
    public function index(Request $request)
    {
        $order_week       = $request->query('order_week');
        $order_today       = $request->query('order_today');
        $order_month      = $request->query('order_month');
        $task_today       = $request->query('task_today');
        $task_week       = $request->query('task_week');
        $task_month      = $request->query('task_month');

        $month      = $request->query('month');
        $date       = Carbon::now();
        $NowDate    = \Carbon\Carbon::now()->format('Y-m-d');
        $AgoDate    = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');  // returns 2016-02-03



        $user = auth()->guard('api')->user();
        $identerprise = $user->client_enterprise_identerprise;

        $orderlist          = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as order_list')
                                ->where('order.order_status', Constant::ORDER_OPEN)
                                ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

        $tasklist          = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as task_list')
                                ->where('order.order_status', Constant::ORDER_OPEN)
                                ->where('order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);

        $orderinprogress    = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as order_inprogress')
                                ->where('order.order_status', Constant::ORDER_INPROGRESS)
                                ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

        $taskinprogress    = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as task_inprogress')
                                ->where('order.order_status', Constant::ORDER_INPROGRESS)
                                ->where('order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);

        $ordercanceled     = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as order_canceled')
                                ->where('order.order_status', Constant::ORDER_CANCELED)
                                ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

        $taskcanceled      = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as task_canceled')
                                ->where('order.order_status', Constant::ORDER_CANCELED)
                                ->where('order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);

        $ordercomplete      = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as order_complete')
                                ->where('order.order_status', Constant::ORDER_COMPLETED)
                                ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

        $taskcomplete      = $this->switchOrderConnection($identerprise)->selectRaw('count(*) as task_complete')
                                ->where('order.order_status', Constant::ORDER_COMPLETED)
                                ->where('order_type_idorder_type', Constant::ORDER_TYPE_EMPLOYEE);

        $vendor             = DB::table('vendor')->select(DB::raw('count(*) as total_vendor'))
                                ->where('status',Constant::STATUS_ACTIVE);

        $enterprisereg      = DB::table('client_enterprise')->select(DB::raw('count(*) as total_enterprisereg'))
                                ->where('enterprise_type_identerprise_type',Constant::ENTERPRISE_TYPE_REGULAR)
                                ->where('status',Constant::STATUS_ACTIVE);

        $enterpriseplus     = DB::table('client_enterprise')->select(DB::raw('count(*) as total_enterpriseplus'))
                                ->where('enterprise_type_identerprise_type',Constant::ENTERPRISE_TYPE_PLUS)
                                ->where('status',Constant::STATUS_ACTIVE);

        $driver             = DB::table('driver')->select(DB::raw('count(*) as total_driver'))
                                ->join('users', 'driver.users_id', '=', 'users.id')
                                ->where('users.status', Constant::STATUS_ACTIVE);

        $employee           = DB::table('employee')->select(DB::raw('count(*) as total_employee'))
                                ->join('users', 'employee.users_id', '=', 'users.id')
                                ->where('users.status', Constant::STATUS_ACTIVE);

        $driverpkwt         = DB::table('driver')->select(DB::raw('count(*) as total_driver_pkwt'))
                                ->join('users', 'driver.users_id', '=', 'users.id')
                                ->where('users.status',Constant::STATUS_ACTIVE)
                                ->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_PKWT_BACKUP);

        $drivercontract      = DB::table('driver')->select(DB::raw('count(*) as total_driver_contract'))
                                ->join('users','driver.users_id', '=', 'users.id')
                                ->where('users.status',Constant::STATUS_ACTIVE)
                                ->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_PKWT);

        $driverfree         = DB::table('driver')->select(DB::raw('count(*) as total_driver_freelance'))
                                ->join('users','driver.users_id','=','users.id')
                                ->where('users.status',Constant::STATUS_ACTIVE)
                                ->where('drivertype_iddrivertype',Constant::DRIVER_TYPE_FREELANCE);

        $displus            = DB::table('users')->select(DB::raw('count(*) as dispatcher_plus'))
                                ->where('status',Constant::STATUS_ACTIVE)
                                ->where('idrole',Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS);

        $disreg             = DB::table('users')->select(DB::raw('count(*) as dispatcher_reguler'))
                                ->where('status',Constant::STATUS_ACTIVE)
                                ->where('idrole',Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER);

        $disondemand        = DB::table('users')->select(DB::raw('count(*) as dispatcher_ondemand'))
                                ->where('status',Constant::STATUS_ACTIVE)
                                ->where('idrole',Constant::ROLE_DISPATCHER_ONDEMAND);

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:


            break;

            case Constant::ROLE_VENDOR:
                $orderlist       = $orderlist->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $orderinprogress = $orderinprogress->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $ordercanceled = $ordercanceled->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $ordercomplete   = $ordercomplete->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $tasklist       = $tasklist->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $taskinprogress = $taskinprogress->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $taskcanceled = $taskcanceled->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);
                $taskcomplete   = $taskcomplete->join('users','order.created_by','users.id')
                    ->where('users.vendor_idvendor',$user->vendor_idvendor);

                $driver         = $driver->where('users.idrole', Constant::ROLE_DRIVER)
                                    ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $employee       = $employee->where('users.idrole', Constant::ROLE_EMPLOYEE)
                                    ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $driverpkwt     = $driverpkwt->where('users.idrole', Constant::ROLE_DRIVER)
                                    ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $drivercontract = $drivercontract->where('users.idrole', Constant::ROLE_DRIVER)
                                    ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $driverfree     = $driverfree->where('users.idrole', Constant::ROLE_DRIVER)
                                    ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $disondemand     = $disondemand->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
                $disreg          = $disreg->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
                $displus         = $displus->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
                $enterpriseplus  = $enterpriseplus->Where('client_enterprise.vendor_idvendor', '=', $user->vendor_idvendor);
                $enterprisereg   = $enterprisereg->Where('client_enterprise.vendor_idvendor', '=', $user->vendor_idvendor);
                $driver          = $driver->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
                $employee        = $employee->Where('users.vendor_idvendor', '=', $user->vendor_idvendor);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $id_client = DB::table('users')
                        ->select('id')
                        ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                        ->where('users.vendor_idvendor', $user->vendor_idvendor)
                        ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                        ->get();

                $array = json_decode(json_encode($id_client), true);

                $orderlist          = $orderlist->wherein('order.client_userid',$array);
                $orderinprogress    = $orderinprogress->wherein('order.client_userid',$array);
                $ordercanceled      = $ordercanceled->wherein('order.client_userid',$array);
                $ordercomplete      = $ordercomplete->wherein('order.client_userid',$array);

                $tasklist           = $tasklist->wherein('order.client_userid',$array);
                $taskinprogress     = $taskinprogress->wherein('order.client_userid',$array);
                $taskcanceled       = $taskcanceled->wherein('order.client_userid',$array);
                $taskcomplete       = $taskcomplete->wherein('order.client_userid',$array);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $orderlist       = $orderlist->where('client_enterprise_identerprise', $user->client_enterprise_identerprise);
                $orderinprogress = $orderinprogress->where('dispatcher_userid',$user->id);
                $ordercanceled   = $ordercanceled->where('dispatcher_userid',$user->id);
                $ordercomplete   = $ordercomplete->with(['dispatcher'])
                                   ->where('order.dispatcher_userid',$user->id);

                $tasklist       = $tasklist->where('client_enterprise_identerprise', $user->client_enterprise_identerprise);
                $taskinprogress = $taskinprogress->where('dispatcher_userid',$user->id);
                $taskcanceled   = $taskcanceled->where('dispatcher_userid',$user->id);
                $taskcomplete   = $taskcomplete->with(['dispatcher'])
                                    ->where('order.dispatcher_userid',$user->id);
                $driver         = $driver->where('users.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                $employee       = $employee->where('users.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            case Constant::ROLE_ENTERPRISE:
                $orderlist      =  $orderlist->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $orderinprogress=  $orderinprogress->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $ordercanceled  =  $ordercanceled->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $ordercomplete  =  $ordercomplete->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $driver         =  $driver->where('users.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                $tasklist       =  $tasklist->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $taskinprogress =  $taskinprogress->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $taskcanceled   =  $taskcanceled->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);
                $taskcomplete   =  $taskcomplete->where('client_enterprise_identerprise',$user->client_enterprise_identerprise);

            break;
        }

        if ($order_today == Constant::BOOLEAN_TRUE) {
            $from_date          = Carbon::parse()->format('Y-m-d');

            $orderlist          = $orderlist->whereDate('order.booking_time', $from_date);
            $orderinprogress    = $orderinprogress->whereDate('order.booking_time', $from_date);
            $ordercanceled      = $ordercanceled->whereDate('order.booking_time', $from_date);
            $ordercomplete      = $ordercomplete->whereDate('order.booking_time', $from_date);
        }
        if ($order_week == Constant::BOOLEAN_TRUE) {

            $orderlist          = $orderlist->whereDate('order.booking_time','<=',$NowDate)
                                   ->whereDate('order.booking_time','>=',$AgoDate);

            $orderinprogress    = $orderinprogress->whereDate('order.booking_time','<=',$NowDate)
            ->whereDate('order.booking_time','>=',$AgoDate);

            $ordercanceled      = $ordercanceled->whereDate('order.booking_time','<=',$NowDate)
            ->whereDate('order.booking_time','>=',$AgoDate);

            $ordercomplete      = $ordercomplete->whereDate('order.booking_time','<=',$NowDate)
            ->whereDate('order.booking_time','>=',$AgoDate);

        }
        if ($order_month == Constant::BOOLEAN_TRUE) {
            $month              = Carbon::parse()->format('m');
            $orderlist          = $orderlist->whereMonth('order.booking_time', $month);
            $orderinprogress    = $orderinprogress->whereMonth('order.booking_time', $month);
            $ordercanceled      = $ordercanceled->whereMonth('order.booking_time', $month);
            $ordercomplete      = $ordercomplete->whereMonth('order.booking_time', $month);
        }

        if ($task_today == Constant::BOOLEAN_TRUE) {
            $from_date          = Carbon::parse()->format('Y-m-d');

            $tasklist           = $tasklist->whereDate('order.booking_time', $from_date);
            $taskinprogress     = $taskinprogress->whereDate('order.booking_time', $from_date);
            $taskcanceled       = $taskcanceled->whereDate('order.booking_time', $from_date);
            $taskcomplete       = $taskcomplete->whereDate('order.booking_time', $from_date);
        }

        if ($task_week == Constant::BOOLEAN_TRUE) {

            $tasklist           = $tasklist->whereDate('order.booking_time','<=',$NowDate)
                                    ->whereDate('order.booking_time','>=',$AgoDate);

            $taskinprogress     = $taskinprogress->whereDate('order.booking_time','<=',$NowDate)
                                    ->whereDate('order.booking_time','>=',$AgoDate);

            $taskcanceled       = $taskcanceled->whereDate('order.booking_time','<=',$NowDate)
                                    ->whereDate('order.booking_time','>=',$AgoDate);

            $taskcomplete       = $taskcomplete->whereDate('order.booking_time','<=',$NowDate)
                                    ->whereDate('order.booking_time','>=',$AgoDate);
            // dd($tasklist);
        }
        if ($task_month == Constant::BOOLEAN_TRUE) {
            $month  = Carbon::parse()->format('m');
            $tasklist       = $tasklist->whereMonth('order.booking_time', $month);
            $taskinprogress = $taskinprogress->whereMonth('order.booking_time', $month);
            $taskcanceled   = $taskcanceled->whereMonth('order.booking_time', $month);
            $taskcomplete   = $taskcomplete->whereMonth('order.booking_time', $month);
        }


        $orderlist          = $orderlist->first();
        $orderinprogress    = $orderinprogress->first();
        $ordercanceled      = $ordercanceled->first();
        $ordercomplete      = $ordercomplete->first();
        $tasklist           = $tasklist->first();
        $taskinprogress     = $taskinprogress->first();
        $taskcanceled       = $taskcanceled->first();
        $taskcomplete       = $taskcomplete->first();
        $vendor             = $vendor->first();
        $enterpriseplus     = $enterpriseplus->first();
        $enterprisereg      = $enterprisereg->first();
        $driver             = $driver->first();
        $employee           = $employee->first();
        $driverpkwt         = $driverpkwt->first();
        $drivercontract     = $drivercontract->first();
        $driverfree         = $driverfree->first();
        $displus            = $displus->first();
        $disreg             = $disreg->first();
        $disondemand        = $disondemand->first();

        $report             = new \stdClass();


        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
                $report->total_order_open = $orderlist->order_list;
                $report->total_order_inprogress = $orderinprogress->order_inprogress;
                $report->total_order_canceled = $ordercanceled->order_canceled;
                $report->total_order_complete = $ordercomplete->order_complete;
                $report->total_task_open = $tasklist->task_list;
                $report->total_task_inprogress = $taskinprogress->task_inprogress;
                $report->total_task_canceled = $taskcanceled->task_canceled;
                $report->total_task_complete = $taskcomplete->task_complete;
                $report->total_vendor = $vendor->total_vendor;
                $report->total_driver = $driver->total_driver;
                $report->total_enterprise_regular = $enterprisereg->total_enterprisereg;
                $report->total_enterprise_plus = $enterpriseplus->total_enterpriseplus;
            break;

            case Constant::ROLE_VENDOR:
                $report->total_order_open = $orderlist->order_list;
                $report->total_order_inprogress = $orderinprogress->order_inprogress;
                $report->total_order_complete = $ordercomplete->order_complete;
                $report->total_task_open = $tasklist->task_list;
                $report->total_task_inprogress = $taskinprogress->task_inprogress;
                $report->total_task_complete = $taskcomplete->task_complete;
                $report->total_enterprise_regular = $enterprisereg->total_enterprisereg;
                $report->total_enterprise_plus = $enterpriseplus->total_enterpriseplus;
                $report->total_dispatcher_regular = $disreg->dispatcher_reguler;
                $report->total_dispatcher_plus = $displus->dispatcher_plus;
                $report->total_dispatcher_ondedmand = $disondemand->dispatcher_ondemand;
                $report->total_driver = $driver->total_driver;
                $report->total_employee = $employee->total_employee;
                $report->total_driver_pkwt_backup = $driverpkwt->total_driver_pkwt;
                $report->total_driver_contract = $drivercontract->total_driver_contract;
                $report->total_driver_freelance = $driverfree->total_driver_freelance;
                $report->total_order_canceled = $ordercanceled->order_canceled;
                $report->total_task_canceled = $taskcanceled->task_canceled;

            break;

            case Constant::ROLE_ENTERPRISE:
                $report->total_driver = $driver->total_driver;
                $report->total_order_open = $orderlist->order_list;
                $report->total_order_inprogress = $orderinprogress->order_inprogress;
                $report->total_order_complete = $ordercomplete->order_complete;
                $report->total_task_open = $tasklist->task_list;
                $report->total_task_inprogress = $taskinprogress->task_inprogress;
                $report->total_task_complete = $taskcomplete->task_complete;
                $report->total_order_canceled = $ordercanceled->order_canceled;
                $report->total_task_canceled = $taskcanceled->task_canceled;
            break ;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $report->total_order_open = $orderlist->order_list;
                $report->total_order_inprogress = $orderinprogress->order_inprogress;
                $report->total_order_complete = $ordercomplete->order_complete;
                $report->total_task_open = $tasklist->task_list;
                $report->total_task_inprogress = $taskinprogress->task_inprogress;
                $report->total_task_complete = $taskcomplete->task_complete;
                $report->total_order_canceled = $ordercanceled->order_canceled;
                $report->total_task_canceled = $taskcanceled->task_canceled;
                // $report->total_dispatcher_reguler = $disreg->dispatcher_reguler;
                // $report->total_enterprise_regular = $enterprisereg->total_enterprisereg;
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $report->total_order_open = $orderlist->order_list;
                $report->total_order_inprogress = $orderinprogress->order_inprogress;
                $report->total_order_complete = $ordercomplete->order_complete;
                $report->total_task_open = $tasklist->task_list;
                $report->total_task_inprogress = $taskinprogress->task_inprogress;
                $report->total_task_complete = $taskcomplete->task_complete;
                $report->total_driver = $driver->total_driver;
                $report->total_employee = $employee->total_employee;
                $report->total_order_canceled = $ordercanceled->order_canceled;
                $report->total_task_canceled = $taskcanceled->task_canceled;
            break;

        }

        return Response::success($report);
    }

    public function grafik()
    {
        $from_date  = Carbon::parse()->format('Y-m-d');
        $end_date   = Carbon::parse()->subDays(7)->format('Y-m-d');
        $user = auth()->guard('api')->user();
        $identerprise = $user->client_enterprise_identerprise;

        $orderlist = $this->switchOrderConnection($identerprise)->select('idorder', 'order.booking_time as order_date')
            ->where('order_status', Constant::ORDER_COMPLETED)
            ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
            ->whereDate('booking_time', '<=', Carbon::now())
            ->whereDate('booking_time', '>', Carbon::now()->subDays(7));

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
            break;

            case Constant::ROLE_VENDOR:
                $orderlist      =  $orderlist->Join('users','order.created_by','=','users.id')
                                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $id_client      = DB::table('users')
                                    ->select('id')
                                    ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                                    ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                                    ->get();

                $array          = json_decode(json_encode($id_client), true);

                $orderlist      =  $orderlist->wherein('order.client_userid',$array);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:

                $orderlist      =  $orderlist->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            case Constant::ROLE_ENTERPRISE:
                $orderlist      =  $orderlist->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

            break;
        }
        $orderlist      =  $orderlist->orderBy('order.booking_time','asc')
                        ->get()
                        ->groupBy(
                            function ($date) {
                                return Carbon::parse($date->order_date)->format('Y-m-d'); // grouping by day
                            }
                            );
                        ;

        if($user->idrole == constant::ROLE_SUPERADMIN){
            $tasklist      =  DB::table('order')->select('order.idorder', 'order.booking_time as order_date')
                            ->join('order_type', 'order.order_type_idorder_type', 'order_type.idorder_type')
                            ->where('order.order_status', Constant::ORDER_COMPLETED)
                            ->where('order_type.idorder_type', Constant::ORDER_TYPE_EMPLOYEE)
                            ->whereDate('order.booking_time', '<=', Carbon::now())
                            ->whereDate('order.booking_time', '>', Carbon::now()->subDays(7))
                            ->orderBy('order.booking_time','asc')
                            ->get()
                            ->groupBy(
                                function ($date) {
                                    return Carbon::parse($date->order_date)->format('Y-m-d'); // grouping by day
                                }
                            )
                            ;
        }else{
            $tasklist      =  DB::table('order')->select('order.idorder', 'order.booking_time as order_date')
                            ->join('order_type', 'order.order_type_idorder_type', 'order_type.idorder_type')
                            ->Join('users','order.created_by','=','users.id')
                            ->where('users.vendor_idvendor', '=', $user->vendor_idvendor)
                            ->where('order.order_status', Constant::ORDER_COMPLETED)
                            ->where('order_type.idorder_type', Constant::ORDER_TYPE_EMPLOYEE)
                            ->whereDate('order.booking_time', '<=', Carbon::now())
                            ->whereDate('order.booking_time', '>', Carbon::now()->subDays(7))
                            ->orderBy('order.booking_time','asc')
                            ->get()
                            ->groupBy(
                                function ($date) {
                                    return Carbon::parse($date->order_date)->format('Y-m-d'); // grouping by day
                                }
                            )
                            ;
        }


        $driver         = DB::table('driver')->select(DB::raw('count(*) as total_driver'))
                        ->join('users', 'driver.users_id', '=', 'users.id')
                        ->where('users.status', Constant::STATUS_ACTIVE);

        $employee       = DB::table('employee')->select(DB::raw('count(*) as total_employee'))
                        ->join('users', 'employee.users_id', '=', 'users.id')
                        ->where('users.status', Constant::STATUS_ACTIVE);

        switch ($user->idrole) {
            case Constant::ROLE_SUPERADMIN:
                break;

            case Constant::ROLE_VENDOR:
                $driver     = $driver->where('users.idrole', Constant::ROLE_DRIVER)
                            ->where("users.vendor_idvendor",$user->vendor_idvendor);
                $employee   = $employee->where('users.idrole', Constant::ROLE_EMPLOYEE)
                            ->where("users.vendor_idvendor",$user->vendor_idvendor);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $driver         = $driver->where('users.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                $employee       = $employee->where('users.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                break;

            case Constant::ROLE_ENTERPRISE:
            break;
        }

        $driver   = $driver->first();
        $employee = $employee->first();

        $label = [];
        $series = [];
        $label2 = [];
        $series2 = [];

        $akhir  = Carbon::now() ;
        $awal   = Carbon::now()->subDays(6);
        $tampung = 0 ;
        $tampung2 = 0 ;

        while (strtotime($awal) <= strtotime($akhir)) {
            $label2[]      = date ("Y-m-d", strtotime($awal));
            $label[]       = date ("Y-m-d", strtotime($awal));
            $tgl_sekarang  = date ("Y-m-d", strtotime($awal));

            foreach ($orderlist as $key2 => $value2) {
                $tgl2 = date ("Y-m-d", strtotime($key2));
                if($tgl_sekarang == $tgl2){
                    $tampung = count($value2);
                }
            }
            $series[]       = $tampung;

            foreach ($tasklist as $key => $value) {
                $tgl = date ("Y-m-d", strtotime($key));
                if($tgl_sekarang == $tgl){
                    $tampung2 = count($value);
                }
            }
            $series2[]       = $tampung2;

            $awal = date ("Y-m-d", strtotime("+1 day", strtotime($awal)));
            $tampung = 0 ;
            $tampung2 = 0 ;
        }

        $orderObj = new \stdClass();
        $orderObj->labels = $label;
        $orderObj->series = $series;

        $taskObj = new \stdClass();
        $taskObj->labels = $label2;
        $taskObj->series = $series2;

        // $grafikarray = [$orderarray, $taskarray];

        if ($user->idrole == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $user->idrole == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS || $user->idrole == Constant::ROLE_ENTERPRISE) {
            $piechart = [$driver];
            $grafik                 = new \stdClass();
            $grafik->grafik         = new \stdClass();
            $grafik->piechart       = $piechart;

            $grafik->grafik->order = $orderObj;
            // $grafik->grafik->task  = $taskObj;
        } else {
            $piechart = [$driver, $employee];
            $grafik                 = new \stdClass();
            $grafik->grafik         = new \stdClass();
            $grafik->piechart       = $piechart;

            $grafik->grafik->order = $orderObj;
            $grafik->grafik->task  = $taskObj;
        }


        return Response::success($grafik);
    }

    //pick connection for cross-server queries
    private function switchOrderConnection($identerprise){
        $order_connection = Order::on('mysql');
        if($identerprise == env('CARS24_IDENTERPRISE')) {
            $order_connection = Order::on('cars24');
        }
        return $order_connection;
    }
}
