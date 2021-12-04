<?php
namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Constants\Constant;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;

class OrdersExport implements FromCollection,WithHeadings
{
    protected $month;
    protected $order_status;

    public function __construct($month,$order_status,$AgoDate,$NowDate,$user,$enterprise_name,$driver_name,$export,$week,$date,$vendor,$trxId,$from,$to)
    {
        $this->month = $month;
        $this->order_status = $order_status;
        $this->user = $user;
        $this->AgoDate = $AgoDate;
        $this->NowDate = $NowDate;
        $this->enterprise_name = $enterprise_name;
        $this->driver_name = $driver_name;
        $this->export = $export;
        $this->week = $week;
        $this->date = $date;
        $this->vendor = $vendor;
        $this->trxId = $trxId;
        $this->from = $from;
        $this->to = $to;
    }

    public function headings(): array {
        return [
            'ID Order','Driver Name', 'Dispatcher Name','Booking Time','Origin Latitude','Origin Longitude',
            'User Fullname','User Phonenumber','Vehicle Owner','Destination Latitude','Destination Longitude',
            'Client Vehicle License','Vehicle Brand','Vehicle Type','Vehicle Transmission','Vehicle Year',
            'Vehicle Color','Message','Order Number','IdOrder Type','Order Status'
        ];
    }

    public function collection()
    {

        $order = new Order;

        $user = auth()->guard('api')->user();

        if($this->order_status == Constant::ORDER_OPEN){
            $status = 'Open';
        }else if($this->order_status == Constant::ORDER_INPROGRESS){
            $status ='Inprogress';
        }else{
            $status ='Completed';
        }

        switch ($user->idrole) {
            
            case Constant::ROLE_SUPERADMIN:
                $order = DB::table('order')
                    ->where('order_status', $this->order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE]);

                if(!empty($this->vendor)){
                    $order = $order->Join('users','order.created_by','=','users.id')
                    ->where('users.vendor_idvendor', $this->vendor);
                }
            break;

            case Constant::ROLE_VENDOR:
                $order = DB::table('order')
                    ->Join('users','order.created_by','=','users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.order_status', $this->order_status);
            break;

            case Constant::ROLE_ENTERPRISE:
                $order = DB::table('order')
                    ->where('order.order_status', $this->order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $id_client = DB::table('users')
                    ->select('id')
                    ->leftjoin('client_enterprise','client_enterprise.identerprise','=','users.client_enterprise_identerprise')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->where('client_enterprise.enterprise_type_identerprise_type', Constant::ENTERPRISE_TYPE_REGULAR)
                    ->get();
                    
                $array = json_decode(json_encode($id_client), true);

                $order = DB::table('order')
                    ->where('order.order_status', $this->order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid',$array);
            break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $order = DB::table('order')
                    ->where('order.order_status', $this->order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
            break;

            case    Constant::ROLE_DISPATCHER_ONDEMAND:
            break;

            default:
            break;
        }

        if (!empty($trxId)) {
            $order->where('trx_id', $this->trxId);
        }

        if (!empty($this->driver_name)) { 
            $order = $order->join('users as users_driver','users_driver.id','=','order.driver_userid')
                           ->where('users_driver.name', 'like', '%' . $this->driver_name . '%');                   
        }

        if (!empty($this->enterprise_name) ) {
            if (empty($this->month)){
                $order = $order->join('client_enterprise','client_enterprise.identerprise','=','order.client_enterprise_identerprise')
                    ->where('client_enterprise.name', 'like', '%' . $this->enterprise_name . '%')
                    ->GROUPBY('order.idorder');
            }else{
                $order = $order->join('client_enterprise','client_enterprise.identerprise','=','order.client_enterprise_identerprise')
                    ->where('client_enterprise.name', 'like', '%' . $this->enterprise_name . '%')                    
                    ->whereMonth('order.booking_time',$this->month);
            }                
        }else if(!empty($this->month)){
            $order = $order->whereMonth('order.booking_time',$this->month);
        }

        if ($this->week == Constant::BOOLEAN_TRUE) {
            $order = $order->whereDate('order.booking_time','<=',$this->NowDate)
                    ->whereDate('order.booking_time','>=',$this->AgoDate);
        }

        if(!empty($this->from) && !empty($this->to)){
            $from_date          = \Carbon\Carbon::createFromFormat("!Y-m-d", $this->from);
            $to_date            = \Carbon\Carbon::createFromFormat("!Y-m-d", $this->to)->addDays(1);
            $order = $order->whereBetween('order.booking_time', [$from_date, $to_date]);
        }

        if ($this->order_status == Constant::ORDER_COMPLETED || $this->order_status == Constant::ORDER_INPROGRESS) {
            $order = $order ->orderBy("order.idorder", "desc");
        }
        $order->leftjoin('users as detail_driver','detail_driver.id','=','order.driver_userid')
            ->leftjoin('users as usr','usr.id','=','order.dispatcher_userid')
            ->leftjoin('order_type','order_type.idorder_type','=','order.order_type_idorder_type')
            ->select(DB::raw("`order`.`idorder`,`detail_driver`.`name` as name_driver,`usr`.`name` as name_dispatcher,`booking_time`,
            `order`.`origin_latitude`,`order`.`origin_longitude`,`order`.`user_fullname`,`order`.`user_phonenumber`,`order`.`vehicle_owner`,
            `order`.`destination_latitude`,`order`.`destination_longitude`,`order`.`client_vehicle_license`,`order`.`vehicle_brand_id`,
            `order`.`vehicle_type`,`order`.`vehicle_transmission`,`order`.`vehicle_year`,`order`.`vehicle_color`,`order`.`message`,
            `order_type`.`name` as name_ordertype,IF(order.order_status = 1, 'Open', IF(order.order_status = 2, 'In Progress', IF(order.order_status = 3, 'Completed', 'Unknown'))) as status_order"));
       
        $orders = $order->get();

        return $orders;
    }

}