<?php

namespace App\Console\Commands;

use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\GlobalHelper;
use App\Http\Helpers\Notification;
use App\Models\B2C\MonthlyBaseOrder;
use App\Models\Driver;
use App\Models\MobileNotification;
use App\Models\Order;
use App\Models\OrderTasks;
use App\Models\Task;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Log;
use DB;

class AutoTaskMonthlyB2C extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monthly:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Create and Dispatch Monthly Driver';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Get all monthly customer data
        $orders = MonthlyBaseOrder::get();

        foreach ($orders as $order) {
            // Get times a week
            $times_a_week = $order->times_a_week;

            try {
                for ($i = 0; $i < $times_a_week; $i++) {
                    DB::beginTransaction();

                    // Generate trx_id
                    $trxId = GlobalHelper::generateTrxId();
                    if (empty($trxId))
                        throw new ApplicationException("orders.invalid_creating_trx_id");

                    // Driver
                    $driver = Driver::where("users_id", $order->driver_userid)
                        ->leftJoin('users', 'driver.users_id', '=', 'users.id')
                        ->first();
                    if (empty($driver))
                        throw new ApplicationException("errors.entity_not_found", ['entity' => 'Driver', 'id' => $order->driver_userid]);

                    // Vendor
                    $vendor = Vendor::where('idvendor', '=', $driver->vendor_idvendor)
                        ->where('status', '=', Constant::STATUS_ACTIVE)
                        ->first();
                    if (empty($vendor))
                        throw new ApplicationException('vendors.failed_to_login_suspend_2');

                    //Cek apakah identerprise sama jika pkwt contract
                    if ($driver->drivertype_iddrivertype == Constant::DRIVER_TYPE_PKWT) {
                        if ($driver->client_enterprise_identerprise != env("B2C_BULANAN_IDENTERPRISE"))
                            throw new ApplicationException("orders.failure_assign_driver_client");
                    }

                    // Arrange order data
                    $new_order_data = [
                        'trx_id'                    => $trxId,
                        'task_template_task_template_id' => env("B2C_BULANAN_TASK_TEMPLATE_ID"),
                        'client_enterprise_identerprise' => env("B2C_BULANAN_IDENTERPRISE"),
                        'client_userid'                  => env("B2C_BULANAN_ADMIN_ID"),
                        'dispatcher_userid'              => env("B2C_BULANAN_DISPATCHER_ID"),
                        'driver_userid'                  => $order->driver_userid,
                        'booking_time'              => Carbon::today()->format("Y-m-d H:i:s"),
                        'origin_latitude'           => $order->origin_latitude,
                        'origin_longitude'          => $order->origin_longitude,
                        'destination_latitude'      => $order->origin_latitude,
                        'destination_longitude'     => $order->origin_longitude,
                        'user_fullname'             => $order->user_fullname,
                        'user_phonenumber'          => $order->user_phonenumber,
                        'client_vehicle_license'    => $order->client_vehicle_license,
                        'vehicle_brand_id'          => $order->vehicle_brand_id,
                        'vehicle_type'              => strtoupper($order->vehicle_type),
                        'vehicle_transmission'      => $order->vehicle_transmission,
                        'vehicle_owner'             => $order->user_fullname,
                        'message'                   => "Task Driver Bulanan (1x Masuk)",
                        'order_type_idorder_type'   => Constant::ORDER_TYPE_ENTERPRISE_PLUS,
                        'order_status'              => Constant::ORDER_INPROGRESS,
                        'status'                    => Constant::STATUS_ACTIVE,
                        'created_by'                => env("B2C_BULANAN_DISPATCHER_ID"),
                        'origin_name'               => $order->origin_name,
                        'destination_name'          => "Lokasi Customer",
                        'vehicle_year'              => $order->vehicle_year,
                        'dispatch_at'               => Carbon::now(),
                        'driver_userid'             => $order->driver_userid,
                        'updated_by'                => env("B2C_BULANAN_DISPATCHER_ID"),
                    ];

                    // Create order
                    $new_order = Order::create($new_order_data);

                    // Order tasks
                    $tasks = Task::where("task_template_id", env("B2C_BULANAN_TASK_TEMPLATE_ID"))->get();

                    foreach ($tasks as $key => $task) {
                        try {
                            OrderTasks::create([
                                'order_idorder'     => $new_order->idorder,
                                'order_task_status' => Constant::ORDER_TASK_NOT_STARTED,
                                'status'            => Constant::STATUS_ACTIVE,
                                'sequence'          => $task->sequence,
                                'name'              => $task->name,
                                'description'       => $task->description,
                                'is_required'       => $task->is_required,
                                'is_need_photo'     => $task->is_need_photo,
                                'is_need_inspector_validation' => $task->is_need_inspector_validation,
                                'latitude'          => $task->latitude,
                                'longitude'         => $task->longitude,
                                'location_name'     => $task->location_name,
                                'created_by'        => env("B2C_BULANAN_DISPATCHER_ID")
                            ]);
                        } catch (Exception $e) {
                            DB::rollBack();
                            throw new ApplicationException("orders.failure_create_order");
                        }
                    }

                    // Update task pertama
                    $updateTaskpertama = OrderTasks::where("order_idorder", $new_order->idorder)
                        ->where("sequence", 1)
                        ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

                    // Notif driver
                    $tokenMobile = MobileNotification::where("user_id", $order->driver_userid)
                        ->first();

                    $fcmRegIds = array();

                    if (!empty($tokenMobile))
                        array_push($fcmRegIds, $tokenMobile->token);

                    if ($fcmRegIds) {
                        $title           = "New Order #{$new_order->trx_id}";
                        $messagebody     = "You have an order";
                        $clickAction     = "orderUpdate";
                        $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody, $clickAction);
                        $returnsendorder = Notification::sendNotification($getGenNotif);
                        if ($returnsendorder == false) {
                            Log::critical("failed send Notification  : {$order->driver_userid} ");
                        }
                    } else {
                        Log::critical("failed send Notification  : {$order->driver_userid} ");
                    }

                    DB::commit();
                }
            } catch (Exception $e) {
                Log::alert($e);
                DB::rollBack();
                throw new ApplicationException("orders.failure_create_order");
            }
        }
    }
}
