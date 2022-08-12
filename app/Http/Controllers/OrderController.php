<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Vendor;
use App\Models\OrderTasks;
use App\Models\Task;
use App\Models\TaskTemplate;
use App\Models\Attendance;
use App\Models\RequestOTP;
use App\Models\MobileNotification;
use App\Models\VehicleType;
use App\Models\VehicleBrand;
use App\Models\B2C\OrderB2C;
use App\Services\Response;
use App\Services\Validate;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use App\Notifications\UserNotification;
use App\Notifications\OrderNotification;
use App\Notifications\NotificationOrderWeb;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Jsonable;
use App\PasswordReset;
use App\Notifications\AccountActivation;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use App\Exports\OrdersExportFromView;
use App\Exports\UserReport;
use Illuminate\Support\Facades\Redirect;
use App\Http\Helpers\GlobalHelper;
use App\Http\Helpers\Notification;
use Illuminate\Support\Facades\Log;
use App\Http\Helpers\Paginator;
use App\Http\Helpers\EventLog;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\Kupon;
use App\Models\Vehicles;
use App\Services\QontakHandler;

class OrderController extends Controller
{
    /**
     * Get current order for driver
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = auth()->guard('api')->user();
        $identerprise = $user->client_enterprise_identerprise;
        $order_connection = $this->switchOrderConnection($identerprise);
        $order = new \stdClass;

        $message = 'messages.success';

        switch ($user->idrole) {
            case Constant::ROLE_DRIVER:
                $order = $order_connection->select('order.*')
                    ->with(['order_tasks', 'task_template', 'driver'])
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.driver_userid', auth()->guard('api')->user()->id)->get();

                if ($order !== NULL) {
                    $checkAttendance = Attendance::where('users_id', auth()->guard('api')->user()->id)
                        ->where("clock_in", ">=", Carbon::today()->toDateString());

                    array_walk($order, function (&$v, $k) use ($checkAttendance) {
                        foreach ($v as $item) {
                            if ($checkAttendance->count() <= 0) {
                                $item->is_today_checkin = false;
                            } else {
                                $item->is_today_checkin = true;
                            }
                        }
                    });
                } else {
                    $message = 'orders.empty_task';
                }
                break;

            case Constant::ROLE_EMPLOYEE:
                $order = $order_connection->select('order.*')
                    ->with(['order_tasks', 'task_template'])
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->where('order.employee_userid', auth()->guard('api')->user()->id)
                    ->get();

                if ($order !== NULL) {
                    $checkAttendance = Attendance::where('users_id', auth()->guard('api')->user()->id)
                        ->where("clock_in", ">=", Carbon::today()->toDateString());

                    array_walk($order, function (&$v, $k) use ($checkAttendance) {
                        foreach ($v as $item) {
                            if ($checkAttendance->count() <= 0) {
                                $item->is_today_checkin = false;
                            } else {
                                $item->is_today_checkin = true;
                            }
                        }
                    });
                } else {
                    $message = 'orders.empty_task';
                }
                break;
        }
        return Response::success($order, $message);
    }
    /**
     * Get detail order
     *
     * @param  [int] id
     * @return [json] Order object
     */
    public function show($id)
    {
        $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;
        $order_connection = $this->switchOrderConnection($identerprise);

        $order = $order_connection->with(["enterprise", "driver", "dispatcher", "order_type", "order_tasks", "task_template", "vehicle_branch"])
            ->where('idorder', $id)->first();

        if ($order !== NULL) {
            $checkAttendance = Attendance::where('users_id', auth()->guard('api')->user()->id)
                ->where("clock_in", ">=", Carbon::today()->toDateString());

            if ($checkAttendance->count() <= 0) {
                $order->is_today_checkin = false;
            } else {
                $order->is_today_checkin = true;
            }
        } else {
            $message = 'orders.empty_task';
        }

        return Response::success($order);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'task_template_id'  => 'int|required|exists:task_template',
            'booking_time'      => "required|date_format:" . Constant::DATE_FORMAT,
            'client_vehicle_license'  => "nullable|min:3|max:12|string",
            'user_fullname' => "required|min:3|max:45|string",
            'user_phonenumber' => "required|min:10|max:45|string",
            'vehicle_owner' => "nullable|min:3|max:45|string",
            'vehicle_brand_id'  => "nullable|string|exists:vehicle_brand,id",
            'vehicle_type'  => "nullable|max:30|string",
            'vehicle_year'  => "nullable|min:4|max:4|string",
            'vehicle_transmission'  => "nullable|string",
            'message'  => "nullable|max:500|string",
            'origin_name'       => 'nullable|string',
            'destination_name'  => 'nullable|string'
        ]);

        $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;

        if($identerprise != env('CARS24_IDENTERPRISE')){
            Validate::request($request->all(), [
                'origin_latitude'   => "required|string",
                'origin_longitude'  => "required|string",
                'destination_latitude'  => "required|string",
                'destination_longitude'  => "required|string",
            ]);
        }

        $vehicle_type   = strtoupper($request->vehicle_type);
        $userId = auth()->guard('api')->user()->id;
        $idRole = auth()->guard('api')->user()->idrole;
        $client_userid = null;
        $dispatcher_userid = null;

        //tambah validasi cek status vendor jika suspend
        $vendor_userlogin = auth()->guard('api')->user()->vendor_idvendor;
        $status_enterprise = auth()->guard('api')->user()->enterprise->enterprise_type;

        if ($status_enterprise->identerprise_type == Constant::ORDER_TYPE_ENTERPRISE_PLUS) {
            $vendor = Vendor::where('idvendor', '=', $vendor_userlogin)
                ->where('status', '=', Constant::STATUS_ACTIVE)
                ->first();

            if (empty($vendor))
                throw new ApplicationException('vendors.failed_to_login_suspend_2');
        }



        $trxId = GlobalHelper::generateTrxId();
        if (empty($trxId))
            throw new ApplicationException("orders.invalid_creating_trx_id");

        if ($idRole == Constant::ROLE_ENTERPRISE) {
            $useridenterprise = User::select('users.*', 'client_enterprise.enterprise_type_identerprise_type as id_type')
                ->join('client_enterprise', 'client_enterprise.identerprise', 'users.client_enterprise_identerprise')
                ->where('id', $userId)
                ->first();

            if (empty($useridenterprise))
                throw new ApplicationException("orders.enterprise_not_set");
        } else {
            $useridenterprise = User::select('users.*', 'client_enterprise.enterprise_type_identerprise_type as id_type')
                ->join('client_enterprise', 'client_enterprise.identerprise', 'users.client_enterprise_identerprise')
                ->where('client_enterprise.identerprise', $identerprise)
                ->where('users.idrole', Constant::ROLE_ENTERPRISE)
                ->where('users.status', Constant::STATUS_ACTIVE)
                ->first();

            if (empty($useridenterprise))
                throw new ApplicationException("orders.enterprise_not_set");
        }

        switch ($idRole) {
            case Constant::ROLE_ENTERPRISE:
                $client_userid = $userId;

                if ($useridenterprise->id_type == Constant::ENTERPRISE_TYPE_REGULAR)
                    $orderType = Constant::ORDER_TYPE_ENTERPRISE;
                else if ($useridenterprise->id_type == Constant::ENTERPRISE_TYPE_PLUS)
                    $orderType = Constant::ORDER_TYPE_ENTERPRISE_PLUS;
                else
                    $orderType = Constant::ORDER_TYPE_ENTERPRISE;
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $client_userid      = $useridenterprise->id;
                $dispatcher_userid  = $userId;
                $orderType          = Constant::ORDER_TYPE_ENTERPRISE_PLUS;
                break;

            default:
                throw new ApplicationException("errors.access_denied");
                break;
        }

        DB::beginTransaction();

        try {
            $order_connection = $this->switchOrderConnection($identerprise);

            $new_order_data = [
                'trx_id' => $trxId,
                'task_template_task_template_id'  => $request->task_template_id,
                'client_enterprise_identerprise' => $identerprise,
                'client_userid'     => $client_userid,
                'dispatcher_userid' => $dispatcher_userid,
                'booking_time'      => $request->booking_time,
                'origin_latitude'   => $request->origin_latitude,
                'origin_longitude'  => $request->origin_longitude,
                'destination_latitude'  => $request->destination_latitude,
                'destination_longitude'  => $request->destination_longitude,
                'user_fullname'  => $request->user_fullname,
                'user_phonenumber'  => $request->user_phonenumber,
                'client_vehicle_license'  => $request->client_vehicle_license,
                'vehicle_brand_id'  => $request->vehicle_brand_id ?? "",
                'vehicle_type'  => $vehicle_type ?? "",
                'vehicle_transmission'  => $request->vehicle_transmission ?? "",
                'vehicle_owner'  => $request->vehicle_owner ?? "",
                'message'  => $request->message ?? "",
                'order_type_idorder_type' => $orderType,
                'order_status'  => Constant::ORDER_OPEN,
                'status'        => Constant::STATUS_ACTIVE,
                'created_by'    => auth()->guard('api')->user()->id,
                'origin_name'       => $request->origin_name,
                'destination_name'  => $request->destination_name,
                'vehicle_year' => $request->vehicle_year ?? 0
            ];

            $order = $order_connection->create($new_order_data);

            // creat or update vehicle type
            $vehicletype = VehicleType::updateOrCreate(
                [
                    'type_name'        => $vehicle_type,
                    'vehicle_brand_id' => $request->vehicle_brand_id
                ],
                [
                    'type_name'        => $vehicle_type,
                    'vehicle_brand_id' => $request->vehicle_brand_id
                ]
            );

            //B2C
            if($identerprise == env('B2C_IDENTERPRISE')){
                // register customer if not exist
                Validate::request($request->all(), [
                    'service_type_id' => "nullable|integer|digits:1",
                    'local_city' => "nullable|integer|digits:1",
                    'insurance' => "nullable|integer|digits:1",
                    'stay' => "nullable|integer|digits:1",
                    'user_email' => "required|string",
                    'user_gender' => "nullable|integer|digits:1",
                    'kupon_id' => "nullable|integer"
                ]);
                $service_type_id = $request->service_type_id;
                $local_city = $request->local_city;
                $insurance = $request->insurance;
                $stay = $request->stay;
                $kupon_id = $request->kupon_id;
                $user_email = $request->user_email;
                $user_gender = $request->user_gender;

                //latest order id
                $latest_id = Order::on('mysql')
                    ->selectRaw('max(idorder) as latest_id')
                    ->first()
                    ->latest_id;

                //randomize sha1 for order link
                $base_val = mt_rand();
                $link = sha1($base_val);

                //do the insert
                // customer data
                // check if customer already registered

                CustomerB2C::updateOrCreate(
                    ['phone' => $request->user_phonenumber],
                    [
                        'phone'     => $request->user_phonenumber,
                        'email'     => $user_email,
                        'fullname'  => $request->user_fullname,
                        'gender'    => $user_gender
                    ]
                );

                $customer_id = CustomerB2C::where('phone', $request->user_phonenumber)->first()->id;

                // order b2c
                $order_b2c_data = [
                    'customer_id'           => $customer_id,
                    'oper_task_order_id'    => $latest_id,
                    'link'                  => $link,
                    'service_type_id'       => $service_type_id,
                    'local_city'            => $local_city,
                    'insurance'             => $insurance,
                    'stay'                  => $stay,
                    'notes'                 => $request->message ?? "",
                    'kupon_id'              => $kupon_id
                ];

                // dd($order_b2c_data);

                OrderB2C::create($order_b2c_data);

                if($kupon_id != null) {
                    Kupon::where('id', $kupon_id)->decrement('jumlah_kupon', 1);
                }

                // BLAST

                // Customer
                $qontakHandler = new QontakHandler();
                $qontakHandler->sendMessage(
                    "62".$request->user_phonenumber,
                    "Order Created",
                    Constant::QONTAK_TEMPLATE_ID_ORDER_CREATED,
                    []
                );

                $genderText = "Wanita";
                if($user_gender == 1)
                    $genderText = "Pria";
                if($user_gender == 2)
                    $genderText = "Lainnya";

                $vehicleBrandName = VehicleBrand::where('id', $request->vehicle_brand_id)->first()->brand_name;

                $paketText = "9 Jam";
                if($service_type_id == 1)
                    $paketText = "12 Jam";
                if($service_type_id == 2)
                    $paketText = "4 Jam";

                $luarKotaText = "Tidak";
                if($local_city == 0){
                    $luarKotaText = "Ya";
                    $stayText = ", Pulang-Pergi";
                    if($stay == 1)
                        $stayText = ", Menginap";
                    $luarKotaText = $luarKotaText . $stayText;
                }

                $asuransiText = "Tidak";
                if($insurance == 1)
                    $asuransiText = "Ya";

                // Dispatcher
                $dispatcherB2cHp = auth()->guard('api')->user()->phonenumber;
                $qontakHandler->sendMessage(
                    "62".$dispatcherB2cHp,
                    "Order Created",
                    Constant::QONTAK_TEMPLATE_NOTIF_DISPATCHER_ADMIN,
                    [
                        [
                            "key"=> "1",
                            "value"=> "nama",
                            "value_text"=> $request->user_fullname
                        ],
                        [
                            "key"=> "2",
                            "value"=> "hp",
                            "value_text"=> $request->user_phonenumber
                        ],
                        [
                            "key"=> "3",
                            "value"=> "email",
                            "value_text"=> $user_email
                        ],
                        [
                            "key"=> "4",
                            "value"=> "gender",
                            "value_text"=> $genderText
                        ],
                        [
                            "key"=> "5",
                            "value"=> "merek",
                            "value_text"=> $vehicleBrandName
                        ],
                        [
                            "key"=> "6",
                            "value"=> "tipe",
                            "value_text"=> $request->vehicle_type
                        ],
                        [
                            "key"=> "7",
                            "value"=> "transmisi",
                            "value_text"=> $request->vehicle_transmission
                        ],
                        [
                            "key"=> "8",
                            "value"=> "plat",
                            "value_text"=> $request->client_vehicle_license
                        ],
                        [
                            "key"=> "9",
                            "value"=> "alamat",
                            "value_text"=> $request->destination_name
                        ],
                        [
                            "key"=> "10",
                            "value"=> "paket",
                            "value_text"=> $paketText
                        ],
                        [
                            "key"=> "11",
                            "value"=> "tanggal",
                            "value_text"=> Carbon::parse($request->booking_time)->format('d-m-Y')
                        ],
                        [
                            "key"=> "12",
                            "value"=> "waktu",
                            "value_text"=> Carbon::parse($request->booking_time)->format('H:i') . " WIB"
                        ],
                        [
                            "key"=> "13",
                            "value"=> "luar_kota",
                            "value_text"=> $luarKotaText
                        ],
                        [
                            "key"=> "14",
                            "value"=> "asuransi",
                            "value_text"=> $asuransiText
                        ],
                        [
                            "key"=> "15",
                            "value"=> "catatan",
                            "value_text"=> $request->message ?? "Tidak ada catatan."
                        ],
                    ]
                );
                // Admin
                $qontakHandler->sendMessage(
                    // override this number
                    "6281365972928",
                    "Order Created",
                    Constant::QONTAK_TEMPLATE_NOTIF_DISPATCHER_ADMIN,
                    [
                        [
                            "key"=> "1",
                            "value"=> "nama",
                            "value_text"=> $request->user_fullname
                        ],
                        [
                            "key"=> "2",
                            "value"=> "hp",
                            "value_text"=> $request->user_phonenumber
                        ],
                        [
                            "key"=> "3",
                            "value"=> "email",
                            "value_text"=> $user_email
                        ],
                        [
                            "key"=> "4",
                            "value"=> "gender",
                            "value_text"=> $genderText
                        ],
                        [
                            "key"=> "5",
                            "value"=> "merek",
                            "value_text"=> $vehicleBrandName
                        ],
                        [
                            "key"=> "6",
                            "value"=> "tipe",
                            "value_text"=> $request->vehicle_type
                        ],
                        [
                            "key"=> "7",
                            "value"=> "transmisi",
                            "value_text"=> $request->vehicle_transmission
                        ],
                        [
                            "key"=> "8",
                            "value"=> "plat",
                            "value_text"=> $request->client_vehicle_license
                        ],
                        [
                            "key"=> "9",
                            "value"=> "alamat",
                            "value_text"=> $request->destination_name
                        ],
                        [
                            "key"=> "10",
                            "value"=> "paket",
                            "value_text"=> $paketText
                        ],
                        [
                            "key"=> "11",
                            "value"=> "tanggal",
                            "value_text"=> Carbon::parse($request->booking_time)->format('d-m-Y')
                        ],
                        [
                            "key"=> "12",
                            "value"=> "waktu",
                            "value_text"=> Carbon::parse($request->booking_time)->format('H:i') . " WIB"
                        ],
                        [
                            "key"=> "13",
                            "value"=> "luar_kota",
                            "value_text"=> $luarKotaText
                        ],
                        [
                            "key"=> "14",
                            "value"=> "asuransi",
                            "value_text"=> $asuransiText
                        ],
                        [
                            "key"=> "15",
                            "value"=> "catatan",
                            "value_text"=> $request->message ?? "Tidak ada catatan."
                        ],
                    ]
                );

                // return Response::success($qontakHandler);
            }

            DB::commit();

            if ($orderType != Constant::ORDER_TYPE_ONDEMAND) {
                if ($status_enterprise->identerprise_type == Constant::ORDER_TYPE_ENTERPRISE_PLUS) {
                    $emails = User::where('idrole', Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS)
                        ->where('vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                        ->where('client_enterprise_identerprise', $identerprise)
                        ->where('status', Constant::STATUS_ACTIVE)
                        ->get();
                } else {
                    $emails = User::where('idrole', Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER)
                        ->where('vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                        ->where('status', Constant::STATUS_ACTIVE)
                        ->get();
                }

                if (count($emails) > 0) {

                    // $data_order2 = $order->leftjoin('vehicle_brand','vehicle_brand.id','=','order.vehicle_brand_id')
                    //             ->select('order.*','vehicle_brand.brand_name')
                    //             ->first();

                    $data_order2 = VehicleBrand::where('vehicle_brand.id', '=', $request->vehicle_brand_id)->first();

                    $orderan =
                        [
                            'greeting' => 'Your Order is',
                            'line' => [
                                'nama' => $request->user_fullname,
                                'plat nomor' => $request->client_vehicle_license,
                                'vehicle Brand' => $data_order2->brand_name,
                                'vehicle Type' => $vehicle_type,
                                'transmission' => $request->vehicle_transmission,
                                'origin' => $request->origin_name,
                                'destination' => $request->destination_name,
                                'booking time' => $request->booking_time,
                                'message' => $request->message,
                                'order created' => auth()->guard('api')->user()->name
                            ],
                        ];
                    foreach ($emails as $email) {
                        $email->notify(
                            new OrderNotification($orderan)
                        );

                        $email->notify(new NotificationOrderWeb($order->idorder));
                    }
                }
            }

            $response = $order_connection->find($order->idorder);

            $array = array(
                'order_idorder' => $order->idorder
            );
            $dataraw = json_encode($array);
            $reason  = 'Create Order #';
            $trxid   = $response->trx_id;
            $model   = 'order';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            return Response::success($response);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("orders.failure_create_order");
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'task_template_id'  => 'int|required|exists:task_template',
            'booking_time'      => "required|date_format:" . Constant::DATE_FORMAT,
            'origin_latitude'   => "required|string",
            'origin_longitude'  => "required|string",
            'destination_latitude'  => "required|string",
            'destination_longitude'  => "required|string",
            'client_vehicle_license'  => "nullable|min:3|max:12|string",
            'user_fullname' => "required|min:3|max:45|string",
            'user_phonenumber' => "required|min:10|max:45|string",
            'vehicle_owner' => "nullable|min:3|max:45|string",
            'vehicle_brand_id'  => "nullable|string",
            'vehicle_type'  => "nullable|max:30|string",
            'vehicle_color'  => "nullable|string",
            'vehicle_type'  => "nullable|string",
            'vehicle_year' => "nullable|min:4|max:4|string",
            'vehicle_transmission'  => "nullable|string",
            'message'  => "nullable|max:500|string",
            'origin_name'       => 'nullable|string',
            'destination_name'  => 'nullable|string'
        ]);

        $vehicle_type       = strtoupper($request->vehicle_type);
        $user               = auth()->guard('api')->user();
        $identerprise       = $user->client_enterprise_identerprise;
        $order_connection   = $this->switchOrderConnection($identerprise);
        $order              = $order_connection->where('idorder', $id)->first();

        //cek apakah id order ada
        if (empty($order))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'idorder', 'id' => $id]);

        //cek orderan status open atau tidak
        if ($order->order_status != Constant::ORDER_OPEN)
            throw new ApplicationException("orders.invalid_open_order");

        //hanya client enterprise dan dispacther plus yg bisa edit
        if (!in_array($user->idrole, [Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS, Constant::ROLE_ENTERPRISE]))
            throw new ApplicationException('errors.access_denied');

        //jika dispacther enterprise plus maka id client harus sama dengan id client yang order
        if ($user->idrole == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS) {
            //Cek apakah identerprise sama jika pkwt contract
            if ($user->client_enterprise_identerprise != $order->client_enterprise_identerprise) {
                throw new ApplicationException("orders.failure_update_order");
            }
        }

        //jika client yg login maka id client harus sama dengan id client yang order
        if ($user->idrole == Constant::ROLE_ENTERPRISE) {
            //Cek apakah identerprise sama jika pkwt contract
            if ($user->client_enterprise_identerprise != $order->client_enterprise_identerprise) {
                throw new ApplicationException("orders.failure_update_order");
            }
        }

        $order->update([
            'task_template_task_template_id'  => $request->task_template_id,
            'booking_time'      => $request->booking_time,
            'origin_latitude'   => $request->origin_latitude,
            'origin_longitude'  => $request->origin_longitude,
            'destination_latitude'  => $request->destination_latitude,
            'destination_longitude'  => $request->destination_longitude,
            'user_fullname'  => $request->user_fullname,
            'user_phonenumber'  => $request->user_phonenumber,
            'client_vehicle_license'  => $request->client_vehicle_license ?? "",
            'vehicle_brand_id'  => $request->vehicle_brand_id ?? "",
            'vehicle_type'  => $vehicle_type ?? "",
            'vehicle_color'  => $request->vehicle_color ?? "",
            'vehicle_transmission'  => $request->vehicle_transmission ?? "",
            'vehicle_owner'  => $request->vehicle_owner ?? "",
            'vehicle_year' => $request->vehicle_year ?? 0,
            'message'  => $request->message ?? "",
            'updated_by'    => auth()->guard('api')->user()->id,
            'order_status'  => Constant::ORDER_OPEN,
            'client_userid' => !empty($order->client_userid) ? $order->client_userid : null,
            'driver_userid' => null,
            'dispatcher_userid' => null,
            'employee_userid' => null,
            'dispatch_at'       => null,
            'origin_name'       => $request->origin_name,
            'destination_name'  => $request->destination_name,
        ]);

        //creat or update vehicle type
        $vehicletype = VehicleType::updateOrCreate(
            [
                'type_name'        => $vehicle_type,
                'vehicle_brand_id' => $request->vehicle_brand_id
            ],
            [
                'type_name'        => $vehicle_type,
                'vehicle_brand_id' => $request->vehicle_brand_id
            ]
        );

        $array = array(
            'order_idorder' => $order->idorder
        );
        $dataraw = json_encode($array);
        $reason  = 'Update Order #';
        $trxid   = $order->trx_id;
        $model   = 'order';
        EventLog::insertLog($trxid, $reason, $dataraw, $model);

        DB::commit();
        return Response::success($order);
    }

    /**
     * Dispatch Order to Driver
     */
    public function cancelorder(Request $request)
    {
        $user               = auth()->guard('api')->user();
        $identerprise       = $user->client_enterprise_identerprise;
        $order_connection   = $this->switchOrderConnection($identerprise);

        if($identerprise == env('CARS24_IDENTERPRISE')){
            Validate::request($request->all(), [
                'idorder'        => "required|integer|exists:cars24.order",
                'reason_cancel'  => "required|string"
            ]);
        } else {
            Validate::request($request->all(), [
                'idorder'        => "required|integer|exists:order",
                'reason_cancel'  => "required|string"
            ]);
        }

        //cek yang login dispatcher bukan
        if (!in_array($user->idrole, [Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS, Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER, Constant::ROLE_ENTERPRISE])) {
            throw new ApplicationException('errors.unauthorized');
        }

        //ditutup karena status open bisa cancel
        //cek apakah order sudah di assign
        $order      = $order_connection->where('idorder', $request->idorder);
        // ->where('driver_userid', '!=', null);
        $data_order = $order->first();

        if (empty($data_order)) {
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'idorder', 'id' => $request->idorder]);
        }

        if ($user->idrole == Constant::ROLE_ENTERPRISE) {
            if ($data_order->client_enterprise_identerprise != $user->client_enterprise_identerprise) {
                throw new ApplicationException('errors.unauthorized');
            }
        }

        if ($data_order->order_status != Constant::ORDER_INPROGRESS && $data_order->order_status != Constant::ORDER_OPEN) {
            throw new ApplicationException("orders.failure_cancel_order");
        }

        //update status order
        $order->update([
            'order_status'  => Constant::ORDER_CANCELED,
            'reason_cancel' => $request->reason_cancel,
            'updated_by'    => $request->user()->id
        ]);

        if($identerprise == env('B2C_IDENTERPRISE')){
            $b2c_order = OrderB2C::where('oper_task_order_id', $request->idorder);

            $b2c_order->update([
                'status'    => 6
            ]);
        }


        // $driver             = Driver::where("users_id",$data_order->driver_userid)
        // ->update(["is_on_order" => false]);

        $tokenMobile          = MobileNotification::where("user_id", $data_order->driver_userid)
            ->first();
        $fcmRegIds            = array();

        if (!empty($tokenMobile))
            array_push($fcmRegIds, $tokenMobile->token);

        if ($fcmRegIds) {
            $title           = "Cancel Order #{$data_order->trx_id}";
            $messagebody     = "Your order #{$data_order->trx_id} has been cancelled";
            $clickAction     = "orderCancel";
            $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody, $clickAction);
            $returnsendorder = Notification::sendNotification($getGenNotif);
            if ($returnsendorder == false) {
                Log::critical("failed send Notification  : {$data_order->driver_userid} ");
            }
        } else {
            Log::critical("failed send Notification  : {$data_order->driver_userid} ");
        }

        $array = array(
            'order_idorder' => $data_order->idorder
        );
        $dataraw = json_encode($array);
        $reason  = 'Cancel Order #';
        $trxid   = $data_order->trx_id;
        $model   = 'order';
        EventLog::insertLog($trxid, $reason, $dataraw, $model);
        return Response::success($order->first());
    }


    /**
     * Dispatch Order to Driver
     */
    public function assign(Request $request)
    {
        $identerprise       = auth()->guard('api')->user()->client_enterprise_identerprise;
        $order_connection   = $this->switchOrderConnection($identerprise);

        if($identerprise == env('CARS24_IDENTERPRISE')){
            Validate::request($request->all(), [
                'idorder'        => "required|integer|exists:cars24.order",
                'driver_userid'  => "required|integer|exists:users,id"
            ]);
        } else {
            Validate::request($request->all(), [
                'idorder'        => "required|integer|exists:mysql.order",
                'driver_userid'  => "required|integer|exists:users,id"
            ]);
        }

        //ditutup dulu
        // $checkAttendance = Attendance::where('users_id', $request->driver_userid)
        //                     ->where("clock_in",">=",Carbon::today()->toDateString())
        //                     ->whereNull("attendance.clock_out")
        //                     ->first();

        // if(empty($checkAttendance)){
        //     throw new ApplicationException("attendance.failure_attendance_task_clockin");
        // }

        //Cek status order
        $order      = $order_connection->with(["vehicle_branch"])
            ->where('idorder', $request->idorder);
        $data_order = $order->first();

        if ($data_order->order_status != Constant::ORDER_OPEN)
            throw new ApplicationException("orders.failure_assign_order");
        //Get driver
        $driver = Driver::where("users_id", $request->driver_userid)
            ->leftJoin('users', 'driver.users_id', '=', 'users.id')
            ->first();

        if (empty($driver))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'Driver', 'id' => $request->driver_userid]);

        //cek status vendor driver
        $vendor = Vendor::where('idvendor', '=', $driver->vendor_idvendor)
            ->where('status', '=', Constant::STATUS_ACTIVE)
            ->first();
        if (empty($vendor))
            throw new ApplicationException('vendors.failed_to_login_suspend_2');

        //cek status driver apakah kosong atau penuh
        // if ($driver->is_on_order == Constant::BOOLEAN_TRUE)
        //     throw new ApplicationException("orders.failure_assign_driver");

        //Cek apakah identerprise sama jika pkwt contract
        if ($driver->drivertype_iddrivertype == Constant::DRIVER_TYPE_PKWT) {
            if ($driver->client_enterprise_identerprise != $data_order->client_enterprise_identerprise)
                throw new ApplicationException("orders.failure_assign_driver_client");
        }

        //cek driver apakah sesuai dengan client jika jenis PKWT
        //cek client_userid
        // $detail_order     = Order::where('idorder',$request->idorder)
        //                     ->leftJoin('users','users.id','=','order.client_userid')
        //                     ->where('users.idrole',Constant::ROLE_ENTERPRISE)
        //                     ->first();
        // $cek_clientdriver = User::where('id',$request->driver_userid)
        //                     ->leftJoin('driver','driver.users_id','=','users.id')
        //                     ->first();

        // if($cek_clientdriver->drivertype_iddrivertype == Constant::DRIVER_TYPE_PKWT){
        //     if($cek_clientdriver->client_enterprise_identerprise != $detail_order->client_enterprise_identerprise){
        //         throw new ApplicationException("orders.failure_assign_driver_client");
        //     }
        // }

        //cek order task sudah di assign belum
        $order_tasks_connection = $this->switchOrderTasksConnection($identerprise);
        $assign_order           = $order_tasks_connection->where("order_idorder", $request->idorder)->get();

        if ($assign_order->count() > 0) {
            throw new ApplicationException("orders.failure_assign_double");
        }

        DB::beginTransaction();
        try {
            $tasks = Task::where("task_template_id", $data_order->task_template_task_template_id)->get();

            foreach ($tasks as $key => $task) {
                try {
                    $order_tasks_connection->create([
                        'order_idorder' => $request->idorder,
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

            $dispatcher_userid = $request->user()->id;
            $order->update(
                [
                    'dispatcher_userid' => $dispatcher_userid,
                    'driver_userid' => $request->driver_userid,
                    'order_status' => Constant::ORDER_INPROGRESS,
                    'dispatch_at' => Carbon::now(),
                    'updated_by' => $request->user()->id,
                ]
            );

            $updateTaskpertama = $order_tasks_connection->where("order_idorder", $request->idorder)
                ->where("sequence", 1)
                ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

            // $driver2 = Driver::where("users_id",$request->driver_userid)
            //         ->update(["is_on_order" => true]);

            $tokenMobile = MobileNotification::where("user_id", $request->driver_userid)
                ->first();

            $fcmRegIds = array();

            if (!empty($tokenMobile))
                array_push($fcmRegIds, $tokenMobile->token);

            if ($fcmRegIds) {
                $title           = "New Order #{$data_order->trx_id}";
                $messagebody     = "You have an order";
                $clickAction	 = "orderUpdate";
                $getGenNotif     = Notification::generateNotification($fcmRegIds, $title, $messagebody, $clickAction);
                $returnsendorder = Notification::sendNotification($getGenNotif);
                if ($returnsendorder == false) {
                    Log::critical("failed send Notification  : {$request->driver_userid} ");
                }
            } else {
                Log::critical("failed send Notification  : {$request->driver_userid} ");
            }

            $taskTemplate        = TaskTemplate::where("task_template_id", $data_order->task_template_task_template_id)->first();
            $vehicle_detail      = VehicleBrand::where('vehicle_brand.id', '=', $data_order->vehicle_brand_id)->first();

            //assign email pending
            //send email to driver
            // $user_driver = User::where('id', $request->driver_userid)
            //                 ->first();
            // $orderan2     =  [
            //                     'greeting' => 'Your have an order',
            //                     'line' => [
            //                         'Transaction ID' => $data_order->trx_id,
            //                         'Task' => $taskTemplate->task_template_name,
            //                         'Vehicle Brand'=> $vehicle_detail->brand_name,
            //                         'Vehicle Type'=> $data_order->vehicle_type,
            //                         'Origin'=> $data_order->origin_name,
            //                         'Destination'=> $data_order->destination_name,
            //                     ],
            //                 ];
            // if ($user_driver){
            //     $user_driver->notify(new OrderNotification($orderan2));
            // }

            $array = array(
                'order_idorder' => $data_order->idorder
            );
            $dataraw = json_encode($array);
            $reason  = 'Assign Order #';
            $trxid   = $data_order->trx_id;
            $model   = 'order';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            DB::commit();

            //send email to enterprise
            $usercliententeprise = User::select('users.*')
                ->where('client_enterprise_identerprise', $data_order->client_enterprise_identerprise)
                ->where('idrole', constant::ROLE_ENTERPRISE)
                ->where('status', Constant::STATUS_ACTIVE)
                ->get();
            $orderan =
                [
                    'greeting' => 'Your order has been assigned to driver',
                    'line' => [
                        'Transaction ID' => $data_order->trx_id,
                        'Driver Name' => $driver->name,
                        'Driver Phone' => $driver->phonenumber,
                        'Task' => $taskTemplate->task_template_name,
                        'Vehicle Brand' => $vehicle_detail->brand_name,
                        'Vehicle Type' => $data_order->vehicle_type,
                        'Origin' => $data_order->origin_name,
                        'Destination' => $data_order->destination_name,
                    ],
                ];

            foreach ($usercliententeprise as $email) {
                $email->notify(new OrderNotification($orderan));
            }

            if($identerprise == env('B2C_IDENTERPRISE')){
                $orderb2c = OrderB2C::where('oper_task_order_id', $request->idorder)
                    ->update([
                        'status' => 1
                    ]);

                // Blast WA
                $orderb2c = OrderB2C::where('oper_task_order_id', $request->idorder)->first();
                $customer_id = $orderb2c->customer_id;
                $link = "https://operdriverstaging.oper.co.id/dashboard/" . $orderb2c->link;
                $phone = CustomerB2C::where('id', $customer_id)->first()->phone;
                $qontakHandler = new QontakHandler();
                $qontakHandler->sendMessage(
                    "62".$phone,
                    "DRIVER ASSIGNED",
                    Constant::QONTAK_TEMPLATE_ID_DRIVER_ASSIGNED,
                    [
                        [
                            "key"=> "1",
                            "value"=> "link",
                            "value_text"=> $link
                        ],
                    ]
                );
            }

            return Response::success($order->first());
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("orders.failure_update_order", ['id' => $request->idorder]);
        }
    }

    public function change_status(Request $request, $id)
    {

        Validate::request($request->all(), [
            'order_status'   => "required|integer"
        ]);

        try {
            $order = Order::where('idorder', $id)->update(
                [
                    'order_status' => $request->order_status,
                    'updated_by' => $request->user()->id,
                ]
            );

            $array = array(
                'order_idorder' => $id
            );
            $dataraw = json_encode($array);
            $reason  = 'Change Status Order #';
            $trxid   = $order->trx_id;
            $model   = 'order';
            EventLog::insertLog($trxid, $reason, $dataraw, $model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("orders.failure_update_order", ['id' => $id]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Get list open order
     *
     * @return [json] list order object
     */
    public function open(Request $request)
    {
        return $this->getListOrderByStatus($request, Constant::ORDER_OPEN);
    }
    /**
     * Get list inprogress order
     *
     * @return [json] list order object
     */
    public function inprogress(Request $request)
    {
        return $this->getListOrderByStatus($request, Constant::ORDER_INPROGRESS);
    }
    /**
     * Get list inprogress order
     *
     * @return [json] list order object
     */
    public function cancel(Request $request)
    {
        return $this->getListOrderByStatus($request, Constant::ORDER_CANCELED);
    }
    /**
     * Get list complete order
     *
     * @return [json] list order object
     */
    public function complete(Request $request)
    {
        return $this->getListOrderByStatus($request, Constant::ORDER_COMPLETED);
    }

    public function show_open($id)
    {
        return $this->getDetailOrderByStatus($id, Constant::ORDER_OPEN);
    }

    public function show_inprogress($id)
    {
        return $this->getDetailOrderByStatus($id, Constant::ORDER_INPROGRESS);
    }

    public function show_complete($id)
    {
        return $this->getDetailOrderByStatus($id, Constant::ORDER_COMPLETED);
    }

    public function show_cancel($id)
    {
        return $this->getDetailOrderByStatus($id, Constant::ORDER_CANCELED);
    }

    private function getListOrderByStatus($request, $order_status)
    {

        $order = new Order;
        $users = new User;
        $AgoDate = \Carbon\Carbon::now()->subWeek()->format('Y-m-d');  // returns 2016-02-03
        $NowDate = \Carbon\Carbon::now()->format('Y-m-d');


        $user               = auth()->guard('api')->user();
        $identerprise       = $user->client_enterprise_identerprise;
        $idvendor           = $user->vendor_idvendor;

        $enterprise_name    = $request->query('enterprise_name');
        $driver_name        = $request->query('driver_name');
        $month              = $request->query('month');
        $export             = $request->query('export');
        $week               = $request->query('week');
        $date               = $request->query('date');
        $vendor             = $request->query('idvendor');
        $trxId              = $request->query('trx_id');
        $from               = $request->query('from');
        $to                 = $request->query('to');

        $order_connection = $this->switchOrderConnection($identerprise);

        switch ($user->idrole) {

            case Constant::ROLE_SUPERADMIN:
                $order = $order_connection
                    ->with(['order_type'])
                    ->where('order_status', $order_status)
                    ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);


                if (!empty($vendor)) {
                    $user_ids = User::where('vendor_idvendor', $vendor)
                        ->pluck('id')->toArray();

                    $order = Order::on('mysql')
                        ->whereIn('created_by', $user_ids)
                        ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order_status', $order_status);
                }
                break;

            case Constant::ROLE_VENDOR:
                $user_ids = User::where('vendor_idvendor', $user->vendor_idvendor)
                    ->pluck('id')->toArray();

                $order = Order::on('mysql')
                    ->whereIn('created_by', $user_ids)
                    ->whereNotIn('order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order_status', $order_status);
                break;

            case Constant::ROLE_ENTERPRISE:
                $order = DB::table('order')
                    ->join('order_type', 'order.order_type_idorder_type', 'order_type.idorder_type')
                    ->where('order.order_status', $order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
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

                $order = $order_connection
                    ->with(['order_type'])
                    ->where('order.order_status', $order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->whereIn('order.client_userid', $array);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $order = $order_connection
                    ->with('order_type')
                    ->where('order.order_status', $order_status)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                break;

            case    Constant::ROLE_DISPATCHER_ONDEMAND:
                break;

            default:
                break;
        }

        if (!empty($trxId)) {
            $order->where('trx_id', $trxId);
        }

        if (!empty($driver_name)) {
            $drivers_user_id = DB::table('driver')
                ->join('users as users_driver', 'users_driver.id', '=', 'driver.users_id')
                ->where('users_driver.name', 'like', '%' . $driver_name . '%')
                ->pluck('driver.users_id')->toArray();
            $order = $order->whereIn('driver_userid', $drivers_user_id);
        }

        if (!empty($enterprise_name)) {
            $enterprises_user_id = DB::table('client_enterprise')
                ->where('name', 'like', '%' . $enterprise_name . '%')
                ->pluck('identerprise')->toArray();

            if (empty($month)) {
                $order = $order->whereIn('client_enterprise_identerprise', $enterprises_user_id);
            } else {
                $order = $order->whereIn('client_enterprise_identerprise', $enterprises_user_id)
                    ->whereMonth('order.booking_time', $month);
            }
        }

        if (!empty($month)) {
            $order = $order->whereMonth('order.booking_time', $month);
        }

        if ($export == Constant::BOOLEAN_TRUE) {
            if (!empty($month)) {
                $file_name = "Order_export" . $order_status . "-" . $month . ".xlsx";
                if($idvendor == env('OLX_IDVENDOR')){
                    Excel::store(new OrdersExportFromView($month, $order_status, $AgoDate, $NowDate, $user, $enterprise_name, $driver_name, $export, $week, $date, $vendor, $trxId, $from, $to, $user->idrole), '/public/file/' . $file_name);
                } else {
                    Excel::store(new OrdersExport($month, $order_status, $AgoDate, $NowDate, $user, $enterprise_name, $driver_name, $export, $week, $date, $vendor, $trxId, $from, $to, $user->idrole), '/public/file/' . $file_name);
                }
                $fileexport = Storage::url('file/' . $file_name);

                return Response::success(["file export" => url($fileexport)]);
            } else {
                throw new ApplicationException("orders.failure_month");
            }
        }

        if ($week == Constant::BOOLEAN_TRUE) {
            $order = $order->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
        }

        if (!empty($from) && !empty($to)) {
            $from_date          = \Carbon\Carbon::createFromFormat("!Y-m-d", $from);
            $to_date            = \Carbon\Carbon::createFromFormat("!Y-m-d", $to)->addDays(1);
            $order = $order->whereBetween('order.booking_time', [$from_date, $to_date]);
        }

        if ($order_status == Constant::ORDER_COMPLETED || $order_status == Constant::ORDER_INPROGRESS) {
            $order = $order->orderBy("order.idorder", "desc");
        }

        //select
        $order->with([
                'vehicle_branch' => function($query){
                    $query->select('id', 'brand_name as vehicle_brand.brand_name');
                },
                'enterprise' => function($query){
                    $query->select('identerprise', 'name as nama_client_enterprise');
                },
                'driver.user' => function($query){
                        $query->select(
                            'id',
                            'name as nama_driver',
                            'profile_picture',
                            'profil_picture_2');
                }
            ])
            ->select(
                'order.*',
                DB::Raw(
                    "IF(order.order_status = 1, 'Open',
                    IF(order.order_status = 2, 'In Progress',
                    IF(order.order_status = 3, 'Completed',
                    IF(order.order_status = 5, 'Canceled', 'Unknown')))) as status_text"));

        $order = $order->get();
        array_walk($order, function (&$v, $k) {
            foreach ($v as $item) {
                if (!empty($item->driver->user->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->driver->user->profile_picture);
                }
                if (!empty($item->driver->user->profile_picture_2)) {
                    $item->profil_picture_2 = env('BASE_API') . Storage::url($item->driver->user->profile_picture_2);
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
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_canceled = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_success    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_open_list       = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_canceled_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);
                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                break;

            case Constant::ROLE_VENDOR:
                $order_open = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_canceled = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_success    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_open_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_canceled_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->Join('users', 'order.created_by', '=', 'users.id')
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE]);

                break;

            case Constant::ROLE_ENTERPRISE:
                $order_open       = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_inprogress = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_canceled = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_open_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_inprogress_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_canceled_list = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                $order_success_list    = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
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
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_inprogress  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_canceled  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_success  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_open_list        = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_OPEN)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_inprogress_list  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_INPROGRESS)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_canceled_list  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_CANCELED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);

                $order_success_list  = DB::table('order')
                    ->where('order.order_status', Constant::ORDER_COMPLETED)
                    ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                    ->wherein('order.client_userid', $array);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:

                if($user->client_enterprise_identerprise == env('CARS24_IDENTERPRISE')){
                    $order_open             = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_OPEN)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_inprogress       = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_INPROGRESS)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_canceled       = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_CANCELED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_success          = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_COMPLETED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_open_list         = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_OPEN)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_inprogress_list   = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_INPROGRESS)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_canceled_list   = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_CANCELED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_success_list      = Order::on('cars24')
                        ->where('order.order_status', Constant::ORDER_COMPLETED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                } else {
                    $order_open         = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_OPEN)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_inprogress   = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_INPROGRESS)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_canceled   = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_CANCELED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_success      = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_COMPLETED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_open_list         = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_OPEN)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_inprogress_list   = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_INPROGRESS)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_canceled_list   = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_CANCELED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);

                    $order_success_list      = DB::table('order')
                        ->where('order.order_status', Constant::ORDER_COMPLETED)
                        ->whereNotIn('order.order_type_idorder_type', [Constant::ORDER_TYPE_ONDEMAND, Constant::ORDER_TYPE_EMPLOYEE])
                        ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise);
                }

                break;

            case    Constant::ROLE_DISPATCHER_ONDEMAND:
                break;

            default:
                break;
        }


        if ($order_date == "month") {

            $order_open         = $order_open->whereMonth('order.booking_time', $month);
            $order_inprogress   = $order_inprogress->whereMonth('order.booking_time', $month);
            $order_canceled     = $order_canceled->whereMonth('order.booking_time', $month);
            $order_success      = $order_success->whereMonth('order.booking_time', $month);


            $order_open_list         = $order_open_list->whereMonth('order.booking_time', $month);
            $order_inprogress_list   = $order_inprogress_list->whereMonth('order.booking_time', $month);
            $order_canceled_list     = $order_canceled_list->whereMonth('order.booking_time', $month);
            $order_success_list      = $order_success_list->whereMonth('order.booking_time', $month);
        } else if ($order_date == "week") {

            $order_open         = $order_open->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_inprogress   = $order_inprogress->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_canceled   = $order_canceled->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_success      = $order_success->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);

            $order_open_list         = $order_open_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_inprogress_list   = $order_inprogress_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_canceled_list   = $order_canceled_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
            $order_success_list      = $order_success_list->whereDate('order.booking_time', '<=', $NowDate)
                ->whereDate('order.booking_time', '>=', $AgoDate);
        } else if ($order_date == "today") {

            $order_open         = $order_open->whereDate('order.booking_time', '=', $NowDate);
            $order_inprogress   = $order_inprogress->whereDate('order.booking_time', '=', $NowDate);
            $order_canceled     = $order_canceled->whereDate('order.booking_time', '=', $NowDate);
            $order_success      = $order_success->whereDate('order.booking_time', '=', $NowDate);

            $order_open_list         = $order_open_list->whereDate('order.booking_time', '=', $NowDate);
            $order_inprogress_list   = $order_inprogress_list->whereDate('order.booking_time', '=', $NowDate);
            $order_canceled_list     = $order_canceled_list->whereDate('order.booking_time', '=', $NowDate);
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

        $order_canceled_list  = $order_canceled_list->selectRaw('order.idorder,order.booking_time as tgl_buat')
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
        $label_canceled = [];
        $series_canceled = [];
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
        $tampung4 = 0;

        while (strtotime($awal) <= strtotime($akhir)) {
            if ($order_date == "month") {
                $label_open[] = date("d", strtotime($awal));
                $label_inprogress[] = date("d", strtotime($awal));
                $label_canceled[] = date("d", strtotime($awal));
                $label_success[] = date("d", strtotime($awal));
            } else {
                $label_open[] = date("Y-m-d", strtotime($awal));
                $label_inprogress[] = date("Y-m-d", strtotime($awal));
                $label_canceled[] = date("Y-m-d", strtotime($awal));
                $label_success[] = date("Y-m-d", strtotime($awal));
            }
            $tgl_sekarang  = date("Y-m-d", strtotime($awal));

            foreach ($order_open_list as $key => $value) {
                $tgl = date("Y-m-d", strtotime($key));

                if ($tgl_sekarang == $tgl) {
                    $tampung = count($value);
                }
            }
            $series_open[]       = $tampung;


            foreach ($order_inprogress_list as $key2 => $value2) {
                $tgl = date("Y-m-d", strtotime($key2));
                if ($tgl_sekarang == $tgl) {
                    $tampung2 = count($value2);
                }
            }
            $series_inprogress[] = $tampung2;


            foreach ($order_canceled_list as $key4 => $value4) {
                $tgl = date("Y-m-d", strtotime($key4));
                if ($tgl_sekarang == $tgl) {
                    $tampung4 = count($value4);
                }
            }
            $series_canceled[] = $tampung4;


            foreach ($order_success_list as $key3 => $value3) {
                $tgl = date("Y-m-d", strtotime($key3));
                if ($tgl_sekarang == $tgl) {
                    $tampung3 = count($value3);
                }
            }
            $series_success[] = $tampung3;

            $awal = date("Y-m-d", strtotime("+1 day", strtotime($awal)));
            $tampung = 0;
            $tampung2 = 0;
            $tampung3 = 0;
            $tampung4 = 0;
        }

        $orderObjopen = new \stdClass();
        $orderObjopen->labels = $label_open;
        $orderObjopen->series = $series_open;

        $orderObjinprogress = new \stdClass();
        $orderObjinprogress->labels = $label_inprogress;
        $orderObjinprogress->series = $series_inprogress;

        $orderObjcanceled = new \stdClass();
        $orderObjcanceled->labels = $label_canceled;
        $orderObjcanceled->series = $series_canceled;

        $orderObjsuccess = new \stdClass();
        $orderObjsuccess->labels = $label_success;
        $orderObjsuccess->series = $series_success;

        $report                         = new \stdClass();
        $report->order_open             = $order_open->count();
        $report->order_inprogress       = $order_inprogress->count();
        $report->order_canceled         = $order_canceled->count();
        $report->order_complete         = $order_success->count();


        $grafik                 = new \stdClass();
        $grafik->total_order    = $report;
        $grafik->grafik         = new \stdClass();
        $grafik->grafik->open          = $orderObjopen;
        $grafik->grafik->inprogress    = $orderObjinprogress;
        $grafik->grafik->canceled      = $orderObjcanceled;
        $grafik->grafik->complete      = $orderObjsuccess;



        return Response::success($grafik);
    }

    private function getDetailOrderByStatus($id, $order_status)
    {
        $user = auth()->guard('api')->user();
        $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;
        $order_connection = $this->switchOrderConnection($identerprise);
        $order = $order_connection->with(["order_tasks", "vehicle_branch"])->where('idorder', $id);

        switch ($user->idrole) {

            case Constant::ROLE_SUPERADMIN:
                $order->where('order_status', $order_status)
                    ->with(['driver', 'enterprise']);
                break;

            case Constant::ROLE_VENDOR:

                $order->leftJoin('users', function ($join) {
                    $join->on('order.client_userid', '=', 'users.id')
                        ->orOn('order.dispatcher_userid', '=', 'users.id');
                })
                    ->where('order.order_status', $order_status)
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->with(['driver', 'enterprise']);

                break;

            case Constant::ROLE_ENTERPRISE:
                $order->where('order_status', $order_status)
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise)
                    ->with(['driver', 'enterprise']);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $order->leftJoin('users', 'order.client_userid', 'users.id')
                    ->select('order.*')
                    ->where('order.order_status', $order_status)
                    ->where('users.vendor_idvendor', $user->vendor_idvendor)
                    ->with(['driver', 'enterprise']);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $order->where('order_status', $order_status)
                    ->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise)
                    ->with(['driver', 'enterprise']);
                break;
        }

        $detail_order = $order->first();
        $url = $detail_order->order_tasks;

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

        if (Constant::ORDER_OPEN != $order_status) {
            if (!empty($detail_order->driver->user->profile_picture)) {
                $pertama = Storage::url($detail_order->driver->user->profile_picture);
                $detail_order->driver->user->profile_picture = env('BASE_API') . $pertama;
            }
        }

        return Response::success($detail_order);
    }

    public function showByTrxId($trxId)
    {
        $user = auth()->guard('api')->user();
        $order = Order::with(["order_tasks"])->where('trx_id', $trxId);

        switch ($user->idrole) {

            case Constant::ROLE_SUPERADMIN:
                $order->with(['driver', 'enterprise']);
                break;

            case Constant::ROLE_VENDOR:
                $order->leftJoin('users', 'order.created_by', 'users.id')
                    ->with(['driver', 'enterprise'])
                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
                break;

            case Constant::ROLE_ENTERPRISE:
                $order->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise)
                    ->with(['driver', 'enterprise']);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $order->leftJoin('users', 'order.client_userid', 'users.id')
                    ->with(['driver', 'enterprise'])
                    ->where('users.vendor_idvendor', $user->vendor_idvendor);
                break;

            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $order->where('order.client_enterprise_identerprise', $user->client_enterprise_identerprise)
                    ->with(['driver', 'enterprise']);
                break;
        }

        $detail_order = $order->first();

        if (Constant::ORDER_OPEN != $detail_order->order_status) {
            if (!empty($detail_order->driver->user->profile_picture)) {
                $pertama = Storage::url($detail_order->driver->user->profile_picture);
                $detail_order->driver->user->profile_picture = env('BASE_API') . $pertama;
            }
        }

        return Response::success($detail_order);
    }

    /**
     * Create Template detail
     *
     * @param  [string] attachment_url
     * @param  [string] submit_latitude
     * @param  [string] submit_longitude
     * @param  [string] idordertask
     * @param  [string] description
     * @param  [string] phonenumber
     * @param  [string] otp
     * @param  [array] tasks
     */
    public function task(Request $request)
    {
        Validate::request($request->all(), [
            'idordertask'       => "required|integer",
            'attachment_url'    => "nullable|image|mimes:jpeg,png,jpg|max:" . Constant::MAX_IMAGE_SIZE,
            'submit_latitude'   => "required|string",
            'submit_longitude'  => "required|string",
            'description'       => "nullable|string",
            'phonenumber'       => "nullable",
            'otp'               => "nullable|string"
        ]);

        $id             = $request->idordertask;
        $user           = auth()->guard('api')->user();
        $identerprise   = $user->client_enterprise_identerprise;
        // NMD jika ada task tengah malam bagaimana?
        // $checkAttendance = Attendance::where('users_id', auth()->guard('api')->user()->id)
        //                 ->where("clock_in",">=",Carbon::today()->toDateString());

        // if($checkAttendance->count() <= 0){
        //     throw new ApplicationException("attendance.failure_attendance_task_clockin");
        // }

        $OrderTasks     = $this->switchOrderTasksConnection($identerprise)->where('idordertask', $id)->first();

        //Validate task status
        if ($OrderTasks->order_task_status != Constant::ORDER_TASK_INPROGRESS)
            throw new ApplicationException("task.invalid_task_status");

        // validasi is need photo;
        if ($OrderTasks->is_need_photo == Constant::BOOLEAN_TRUE) {
            if (empty($request->hasfile('attachment_url')))
                throw new ApplicationException("orders.failure_required_image");
        }

        //hanya driver yang ada validasi otp
        if ($user->idrole == Constant::ROLE_DRIVER) {

            //validasi otp
            if ($OrderTasks->is_need_inspector_validation == Constant::BOOLEAN_TRUE) {

                $no_hp  = GlobalHelper::replace_hp($request->phonenumber);
                $otp    =  RequestOTP::where('idordertask', $id)
                    ->where('phonenumber', $no_hp)
                    ->first();

                if (empty($request->phonenumber))
                    throw new ApplicationException("errors.failure_require_field", ['field' => 'Phone Number']);

                if (empty($otp) || ($otp->otp != $request->otp))
                    throw new ApplicationException("otp.invalid");
            }
        }

        if ($OrderTasks) {

            try {
                if ($request->hasfile('attachment_url')) {
                    $path = Storage::putFile("/public/images/ordertask", $request->file('attachment_url'));
                    $OrderTasks->update([
                        'attachment_url'   => $path,
                        'updated_by'       => auth()->guard('api')->user()->id,
                        'last_update_status' => null
                    ]);
                }
                $OrderTasks->update([
                    'updated_by'        => auth()->guard('api')->user()->id,
                    'submit_latitude'   => $request->submit_latitude,
                    'submit_longitude'  => $request->submit_longitude,
                    'description'       => $request->description,
                    'order_task_status' => Constant::ORDER_TASK_COMPLETED,
                    'last_update_status' => Carbon::now()->format('Y-m-d H:i:s')
                ]);

                if (!empty($OrderTasks->attachment_url)) {
                    $OrderTasks->attachment_url = Storage::url($OrderTasks->attachment_url);
                }

                //cek status all task
                $check_allstatus = $this->check_allstatustask($OrderTasks->order_idorder, $identerprise);
                if ($check_allstatus == true) {
                    //update order status

                    $order_connection = $this->switchOrderConnection($identerprise);

                    $order = $order_connection->where('idorder', $OrderTasks->order_idorder)->update(
                        [
                            'order_status' => Constant::ORDER_COMPLETED,
                            'updated_by'   => auth()->guard('api')->user()->id,
                        ]
                    );

                    $detail_order     = $order_connection->where('idorder', $OrderTasks->order_idorder)->first();
                    // if( $user->idrole == Constant::ROLE_DRIVER) {
                    //     $driver           = Driver::where("users_id",$detail_order->driver_userid)
                    //                         ->update(["is_on_order" => false]);
                    // }else{
                    //     $employee           = Employee::where("users_id",$detail_order->employee_userid)
                    //                         ->update(["is_on_task" => false]);
                    // }

                    $email_dispatcher = $this->send_email($detail_order->dispatcher_userid);
                    $email_client     = $this->send_email($detail_order->client_userid);
                    $orderan =
                        [
                            'greeting' => 'Your following order is completed',
                            'line' => [
                                'nama' => $detail_order->user_fullname,
                                'plat nomor' => $detail_order->client_vehicle_license,
                                'vehicle Brand' => $detail_order->vehicle_brand_id,
                                'vehicle Type' => $detail_order->vehicle_type,
                                'transmission' => $detail_order->vehicle_transmission,
                                'origin' => $detail_order->origin_name,
                                'destination' => $detail_order->destination_name,
                                'booking time' => $detail_order->booking_time,
                                'message' => $detail_order->message
                            ],
                        ];

                    if (!empty($email_dispatcher)) {
                        $email_dispatcher->notify(
                            new OrderNotification($orderan)
                        );
                    }

                    if (!empty($email_client)) {
                        $email_client->notify(new OrderNotification($orderan));
                    }

                    $islastorder   = $this->switchOrderTasksConnection($identerprise)->where('order_idorder', $OrderTasks->order_idorder)->orderby('idordertask', 'desc')->first();
                    $id_nexttask   = $request->idordertask + 1;
                    $next_task     = $this->switchOrderTasksConnection($identerprise)->where('idordertask', $id_nexttask)
                        ->where('order_idorder', $OrderTasks->order_idorder)
                        ->first();

                    if ($id_nexttask == $islastorder->idordertask) {
                        $is_last_order = "true";
                    } else {
                        $is_last_order = "false";
                    }

                    $updateTaskselanjutnya = $this->switchOrderTasksConnection($identerprise)->where("order_idorder", $OrderTasks->order_idorder)
                        ->where('idordertask', $id_nexttask)
                        ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

                    if($identerprise == env('B2C_IDENTERPRISE')){
                        $b2c_order = OrderB2C::where('oper_task_order_id', $OrderTasks->order_idorder);

                        $b2c_order->update([
                            'status'    => 4
                        ]);

                        $order_b2c = OrderB2C::where('oper_task_order_id', $OrderTasks->order_idorder)->first();
                        $customer_id = $order_b2c->customer_id;
                        $phone = CustomerB2C::where('id', $customer_id)->first()->phone;
                        $qontakHandler = new QontakHandler();
                        $qontakHandler->sendMessage(
                            "62".$phone,
                            "Order Verified",
                            Constant::QONTAK_TEMPLATE_VERIFIED,
                            []
                        );
                    }

                    return Response::success(["is_last_order" => $is_last_order, "next_task" => $next_task], 'orders.complete_order');
                }

                $islastorder   = $this->switchOrderTasksConnection($identerprise)->where('order_idorder', $OrderTasks->order_idorder)->orderby('idordertask', 'desc')->first();
                $id_nexttask   = $request->idordertask + 1;
                $next_task     = $this->switchOrderTasksConnection($identerprise)->where('idordertask', $id_nexttask)
                    ->where('order_idorder', $OrderTasks->order_idorder)
                    ->first();

                if ($id_nexttask == $islastorder->idordertask) {
                    $is_last_order = "true";
                } else {
                    $is_last_order = "false";
                }

                $updateTaskselanjutnya = $this->switchOrderTasksConnection($identerprise)->where("order_idorder", $OrderTasks->order_idorder)
                    ->where('idordertask', $id_nexttask)
                    ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

                $array = array(
                    'order_tasks_idordertask' => $id
                );
                $dataraw = json_encode($array);
                $reason  = 'Submit Task Order #';
                $trxid   = $id;
                $model   = 'task';
                EventLog::insertLog($trxid, $reason, $dataraw, $model);

                return Response::success(["is_last_order" => $is_last_order, "next_task" => $next_task]);
            } catch (Exception $e) {
                throw new ApplicationException("orders.failure_update_task_order");
            }
        }
    }

    /**
     * task skip check
     *
     * @param  [string] check_skip
     * @param  [array] check:isquired
     */

    public function skip_task(Request $request)
    {
        Validate::request($request->all(), [
            'idordertask'       => "required|integer|exists:order_tasks",
        ]);

        $OrderTasks    = OrderTasks::where('idordertask', $request->idordertask)->first();
        $islastorder   = OrderTasks::where('order_idorder', $OrderTasks->order_idorder)
            ->orderby('idordertask', 'desc')->first();

        if ($islastorder->idordertask == $OrderTasks->idordertask) {
            throw new ApplicationException('task.cannot_skip_last_task');
        }

        if ($OrderTasks->is_required == Constant::BOOLEAN_TRUE)
            throw new ApplicationException('task.cannot_skip_task');

        $OrderTasks->update([
            'order_task_status' => Constant::ORDER_TASK_SKIPPED,
            'last_update_status' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_by'        => auth()->guard('api')->user()->id
        ]);

        //count task
        $countOrderTasks = OrderTasks::where('order_idorder', $OrderTasks->order_idorder)->count();

        //get next task
        $nextIdordertask = $OrderTasks->idordertask + 1;
        $nextTask = OrderTasks::where('idordertask', $nextIdordertask)
            ->where('order_idorder', $OrderTasks->order_idorder)
            ->first();

        $is_last_order = false;
        if (!empty($nextTask)) {
            $is_last_order = $nextTask->sequence == $countOrderTasks ? true : false;
        }

        $updateTaskselanjutnya = OrderTasks::where("order_idorder", $OrderTasks->order_idorder)
            ->where('idordertask', $nextIdordertask)
            ->update(["order_task_status" => Constant::ORDER_TASK_INPROGRESS]);

        $array = array(
            'order_tasks_idordertask' => $request->idordertask
        );
        $dataraw = json_encode($array);
        $reason  = 'Skip Task Order #';
        $trxid   =  $request->idordertask;
        $model   = 'task';
        EventLog::insertLog($trxid, $reason, $dataraw, $model);

        //response
        $response = new \stdClass();
        $response->is_last_order = $is_last_order;
        $response->next_task = $nextTask;

        return Response::success($response);
    }

    /** FOR DRIVER
     *
     * Get list history
     *
     * @return [json]
     */
    public function history(Request $request)
    {
        $user   = auth()->guard('api')->user();
        $identerprise = $user->client_enterprise_identerprise;
        $order_connection = $this->switchOrderConnection($identerprise);

        switch ($user->idrole) {
            case Constant::ROLE_DRIVER:
                $order = $order_connection->select('order.*')
                    ->selectRaw('DATE_FORMAT(booking_time, "%a| %d %M %Y| %H:%i") as booking_time_format')
                    ->with(['enterprise', 'task_template' => function ($query){
                        $query->select(['task_template_id', 'task_template_name']);
                    }])
                    ->whereIn('order.order_status', [Constant::ORDER_COMPLETED, Constant::ORDER_CANCELED])
                    ->where('order.driver_userid', auth()->guard('api')->user()->id)
                    ->orderBy('idorder', 'desc');
                break;

            case Constant::ROLE_EMPLOYEE:
                $order = $order_connection->select('order.*')
                    ->selectRaw('DATE_FORMAT(booking_time, "%a| %d %M %Y| %H:%i") as booking_time_format')
                    ->with(['enterprise', 'task_template' => function ($query){
                        $query->select(['task_template_id', 'task_template_name']);
                    }])
                    ->whereIn('order.order_status', [Constant::ORDER_COMPLETED, Constant::ORDER_CANCELED])
                    ->where('order.employee_userid', auth()->guard('api')->user()->id)
                    ->orderBy('idorder', 'desc');
                break;
        }

        return Response::success($order->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
    }

    public function history_detail($id)
    {
        $user = auth()->guard('api')->user();
        $identerprise = $user->client_enterprise_identerprise;
        $order_connection = $this->switchOrderConnection($identerprise);

        switch ($user->idrole) {
            case Constant::ROLE_DRIVER:
                $order = $order_connection->select('order.*')
                    ->selectRaw('DATE_FORMAT(booking_time, "%W | %d %M %Y | %H:%i") as booking_time_format')
                    ->with([
                            "order_tasks",
                            "enterprise" => function($query){$query->select('identerprise', 'name');},
                            "vehicle_branch" => function($query){$query->select('id', 'brand_name');},
                            "task_template" => function($query){$query->select('task_template_id', 'task_template_name');}
                        ])
                    ->where('idorder', $id)
                    ->whereIn('order_status', [Constant::ORDER_COMPLETED, Constant::ORDER_CANCELED])->first();
                break;

            case Constant::ROLE_EMPLOYEE:
                $order = $order_connection->select('order.*')
                    ->selectRaw('DATE_FORMAT(booking_time, "%W | %d %M %Y | %H:%i") as booking_time_format')
                    ->with([
                            "order_tasks",
                            "enterprise" => function($query){$query->select('identerprise', 'name');},
                            "task_template" => function($query){$query->select('task_template_id', 'task_template_name');}
                        ])
                    ->where('idorder', $id)
                    ->whereIn('order_status', [Constant::ORDER_COMPLETED, Constant::ORDER_CANCELED])->first();
                break;
        }
        if (null == $order) {
            throw new ApplicationException('errors.entity_not_found', ['entity' => 'Order', 'id' => $id]);
        }

        array_walk($order->order_tasks, function (&$v, $k) {
            foreach ($v as $item) {

                if (!empty($item->attachment_url)) {
                    $item->attachment_url = env('BASE_API') . Storage::url($item->attachment_url);
                }
                if (!empty($item->last_update_status)) {
                    $item->last_update_status_time = Carbon::parse($item->last_update_status)->format('H:i');
                    $item->last_update_status = Carbon::parse($item->last_update_status)->format('l | d M Y | H:i');
                }
            }
        });

        return Response::success($order);
    }

    /**
     * task skip check
     *
     * @param  [string] id_order
     * @param  [array] check:isquired
     */

    private function check_allstatustask($id, $identerprise)
    {
        $order_tasks_connection = $this->switchOrderTasksConnection($identerprise);
        $all_ordertask    = $order_tasks_connection->where('order_idorder', $id)
            ->where('order_task_status', Constant::ORDER_TASK_NOT_STARTED)
            ->get();
        if ($all_ordertask->count() >= 1) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * task skip check
     *
     * @param  [string] id_order
     * @param  [array] check:isquired
     */

    private function send_email($user_id)
    {
        $emails = User::where('id', $user_id)
            ->where('status', Constant::STATUS_ACTIVE)
            ->first();

        return $emails;
    }

    public function template(Request $request)
    {
        $idrole   = auth()->guard('api')->user()->idrole;
        $iduser   = auth()->guard('api')->user()->id;
        $identerprise   = auth()->guard('api')->user()->client_enterprise_identerprise;

        $file_name = "Template" . $idrole . "-" . $iduser . ".xlsx";
        Excel::store(new UserReport($idrole, $iduser, $identerprise), '/public/file/' . $file_name);
        $fileexport = Storage::url('file/' . $file_name);
        return Response::success(["file export" => url($fileexport)]);
    }

    // Get all unavailable dates array
    public function unavailableDates(){
        // For now let's test with all orders
        // $dates = Order::all()->pluck('booking_time');
        $dates = Order::selectRaw('booking_time, @booking_date:=(DATE(booking_time)) as booking_date, COUNT(@booking_date) as booking_date_count')
            ->whereDate('booking_time', '>=', Carbon::now())
            ->groupBy('booking_date')
            ->orderBy('booking_date', 'desc')
            ->get();

        // Nearest date
        $latestDate = Carbon::parse($dates->first()->booking_date);
        $todayDate = Carbon::today();
        $dayDiff = $latestDate->diffInDays($todayDate);

        // Change the date count (at this moment 1) to 10 later on after demo
        $dates = $dates->where('booking_date_count', 1)->pluck('booking_date');

        $days = [];

        for($i = 0; $i <= $dayDiff; $i++){
            array_push($days, ["booking_date" => Carbon::today()->addDays($i)->format("Y-m-d")]);
        }

        $dayCollection = collect($days);

        $availableDatesCollection = $dayCollection->whereNotIn('booking_date', $dates);

        if($availableDatesCollection->count() === 0){
            $nearestDate = $latestDate->addDays(1)->format("Y-m-d");
        } else {
            $nearestDate = $availableDatesCollection->first()['booking_date'];
        }

        $dateArray = [
            "unavailable_dates" => $dates,
            "nearest_date" => $nearestDate,
        ];

        return Response::success($dateArray);

        // Don't forget to only get dates onward from today
    }

    //get connection for cross-server queries
    private function switchOrderConnection($identerprise){
        $connection = Order::on('mysql');
        if($identerprise == env('CARS24_IDENTERPRISE')) {
            $connection = Order::on('cars24');
        }
        return $connection;
    }

    private function switchOrderTasksConnection($identerprise){
        $connection = OrderTasks::on('mysql');
        if($identerprise == env('CARS24_IDENTERPRISE')) {
            $connection = OrderTasks::on('cars24');
        }
        return $connection;
    }
}
