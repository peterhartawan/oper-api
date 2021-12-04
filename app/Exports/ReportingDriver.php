<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Carbon\Carbon;
use App\Models\Order;
use App\Constants\Constant;
use App\Models\OrderTasks;
use App\Models\Task;
use DB;
use Maatwebsite\Excel\Concerns\FromView;

class ReportingDriver implements FromView
{
    protected $idtemplate;
    protected $daterange;

    public function __construct($idtemplate,$daterange)
    {
        $this->daterange       = $daterange;
        $this->idtemplate      = $idtemplate;
    }

    public function view(): View
    {
        $user           = auth()->guard('api')->user();
        $from_date      = Carbon::parse(substr($this->daterange, 0, 10))->format('Y-m-d');
        $to_date        = Carbon::parse(substr($this->daterange, -10))->format('Y-m-d 23:59:59');

        $tasks          = Task::where("task_template_id", $this->idtemplate)
                          ->orderBy('sequence','asc')
                          ->get();
                    
        // switch ($user->idrole) {

        //     case Constant::ROLE_SUPERADMIN:
        //         $order2 = Order::select('order.*');
        //     break;

        //     case Constant::ROLE_VENDOR:
        //         $order2 = Order::select('order.*')
        //             ->Join('users','order.created_by','=','users.id')
        //             ->where('users.vendor_idvendor', $user->vendor_idvendor);
        //     break;

        //     case Constant::ROLE_ENTERPRISE:
        //         $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
        //     break;

        //     case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
        //         $id_client = DB::table('users')
        //             ->select('id')
        //             ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
        //             ->where('users.vendor_idvendor', $user->vendor_idvendor)
        //             ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
        //             ->get();
                    
        //         $array = json_decode(json_encode($id_client), true);

        //         $order2 = Order::wherein('order.client_userid',$array);
        //     break;

        //     case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
        //         $order2 = Order::where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
        //     break;

        //     default:
        //     break;
        // } 
    
    
        // $templatetask2 = $order2->select('order.idorder',DB::raw("DATE_FORMAT(order.booking_time, '%d-%M-%Y ') as date"), DB::raw("DATE_FORMAT(order.booking_time, '%H:%i:%s ') as time"),
        //                 'order.client_vehicle_license', 'vehicle_brand.brand_name', 'order.vehicle_type' , 'order.vehicle_year', 
        //                 'order.origin_name', 'order.destination_name', DB::raw('CONCAT(order.origin_name, "-", order.destination_name) AS route'),
        //                 'usr.name as name_dispatcher', 'driver.name as name_driver', DB::raw("DATE_FORMAT(order.dispatch_at, '%H:%i:%s ') as dispatch_time") , 'order.dispatch_at','order.booking_time',  'order.updated_at', 'booking_time',
        //                 'order_type.name as name_ordertype')
        //                 ->leftjoin('users as driver','driver.id','=','order.driver_userid')
        //                 ->leftjoin('order_type','order_type.idorder_type','=','order.order_type_idorder_type')
        //                 ->leftjoin('vehicle_type', 'order.vehicle_type', '=', 'vehicle_type.type_name')
        //                 ->leftjoin('users as usr','usr.id','=','order.dispatcher_userid')
        //                 ->leftjoin('vehicle_brand', 'vehicle_brand.id', '=', 'vehicle_type.vehicle_brand_id')
        //                 ->whereBetween('order.booking_time', [$from_date, $to_date])
        //                 ->where('task_template_task_template_id',$this->idtemplate)
        //                 ->where('order.order_status', Constant::ORDER_COMPLETED)
        //                 ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
        //                 ->get();

        // $templatetask3 = $templatetask2->map(function ($item, $key) {
        //     $item['template'] = OrderTasks::select(DB::raw("DATE_FORMAT(last_update_status, '%d-%M-%Y %H:%i:%s') as date"), DB::raw("DATE_FORMAT(last_update_status, '%H:%i:%s ') as time"), 'order_tasks.*')
        //                         ->where('order_idorder',$item->idorder)
        //                         ->orderby('sequence','asc')
        //                         ->get();

        //     return $item;
        // });


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
        
        $order2 = $order2->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);
        

        $templatetask2 = $order2->select('order.idorder',DB::raw("DATE_FORMAT(order.booking_time, '%d-%M-%Y ') as date"), DB::raw("DATE_FORMAT(order.booking_time, '%H:%i:%s ') as time"),
        'order.client_vehicle_license', 'vehicle_brand.brand_name', 'order.vehicle_type' , 'order.vehicle_year', 
        'order.origin_name', 'order.destination_name', DB::raw('CONCAT(order.origin_name, "-", order.destination_name) AS route'),
        'usr.name as name_dispatcher', 'driver.name as name_driver', DB::raw("DATE_FORMAT(order.dispatch_at, '%H:%i:%s ') as dispatch_time") , 'order.dispatch_at','order.booking_time',  'order.updated_at', 'booking_time',
        'order_type.name as name_ordertype')
        ->leftjoin('users as driver','driver.id','=','order.driver_userid')
        ->leftjoin('order_type','order_type.idorder_type','=','order.order_type_idorder_type')
        ->leftjoin('users as usr','usr.id','=','order.dispatcher_userid')
        ->leftjoin('vehicle_brand', 'vehicle_brand.id', '=', 'order.vehicle_brand_id')
        ->leftJoin('vehicle_type', function($join)
                         {
                             $join->on('vehicle_type.type_name', '=', 'order.vehicle_type');
                             $join->on('vehicle_type.vehicle_brand_id','=','order.vehicle_brand_id');
                         })
                        ->whereBetween('order.booking_time', [$from_date, $to_date])
                        ->where('task_template_task_template_id',$this->idtemplate)
                        ->where('order.order_status', Constant::ORDER_COMPLETED)
                        ->groupBy('order.idorder')
                        ->get();

        $templatetask3 = $templatetask2->map(function ($item, $key) {
            $item['template'] = OrderTasks::select(DB::raw("DATE_FORMAT(last_update_status, '%d-%M-%Y %H:%i:%s') as date"), DB::raw("DATE_FORMAT(last_update_status, '%H:%i:%s ') as time"), 'order_tasks.*')
                                ->where('order_idorder',$item->idorder)
                                ->orderby('sequence','asc')
                                ->get();

            return $item;
        });

        return view('reportdriver', [
            'detailorder' => $templatetask3,
            'tasks'    => $tasks
        ]);
    }
}
