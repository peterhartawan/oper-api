<?php
namespace App\Imports;

use DB;
use App\User;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\OrderTasks;
use App\Models\Task;
use App\Models\Attendance;
use App\Models\RequestOTP;
use App\Models\MobileNotification;
use App\Models\VehicleType;
use App\Models\Places;
use App\Constants\Constant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Http\Helpers\GlobalHelper;
use App\Http\Helpers\Notification;
use App\Notifications\OrderNotification;
use App\Services\Response;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class DataInputOrder implements ToCollection, WithChunkReading
{
    protected $request;
    private $result;

    public function __construct($request)
    {
        $this->request = $request;
    }

    public function chunkSize(): int
    {
        return 300;
    }

    public function getResult()
    {
        return $this->result;
    }

    public function collection(Collection $rows)
    {
        $i = 0;
        $success = 0;
        $failure = 0;

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                $i++;
                if ($i < 2) {
                    continue;
                }

                $task_template_id       = trim($row[1]);
                $booking_time2          = Carbon::parse($row[2])->format('Y-m-d');
                $booking_time3          = Carbon::parse($row[3])->format('H:i:s');
                $booking_time            = Carbon::parse($booking_time2." ".$booking_time3)->format('Y-m-d H:i:s');
                $idorigin               = trim($row[7]);
                $iddestination          = trim($row[8]);
                $client_vehicle_license = trim($row[12]);
                $user_fullname          = trim($row[5]);
                $user_phonenumber       = trim($row[6]);
                $vehicle_owner          = trim($row[13]);
                $vehicle_brand_id       = trim($row[9]);
                $vehicle_type           = trim($row[10]);
                $vehicle_year           = trim($row[14]);
                $vehicle_transmission   = trim($row[11]);
                $message                = trim($row[4]);

                $places_origin          = Places::query()
                                        ->where('places.status', Constant::STATUS_ACTIVE)
                                        ->where('places.idplaces', $idorigin)->first();

                $report_eror            = "Cek Id Template , Origin and Destination Line : ".trim($row[0]);

                if (empty($places_origin)) {
                    $failure++;
                    Session::put('status_importorder',"origin");
                    continue;
                } else {
                    $origin_latitude  = $places_origin->latitude;
                    $origin_longitude = $places_origin->longitude;
                    $origin_name      = $places_origin->name;
                }

                $places_destination   = Places::query()
                                ->where('places.status', Constant::STATUS_ACTIVE)
                                ->where('places.idplaces', $iddestination)->first();

                if (empty($places_destination)) {
                    $failure++;
                    Session::put('status_importorder',"destination");
                    continue;
                } else {
                    $destination_latitude  = $places_destination->latitude;
                    $destination_longitude = $places_destination->longitude;
                    $destination_name      = $places_destination->name;
                }

                $vehicle_type   = strtoupper($vehicle_type);
                $userId         = auth()->guard('api')->user()->id;
                $idRole         = auth()->guard('api')->user()->idrole;
                $identerprise   = auth()->guard('api')->user()->client_enterprise_identerprise;
                $client_userid  = null;
                $trxId          = GlobalHelper::generateTrxId();

                if (empty($trxId)) {
                    $failure++;
                    continue;
                }

                $useridenterprise = User::select('users.*', 'client_enterprise.enterprise_type_identerprise_type as id_type')
                                    ->join('client_enterprise', 'client_enterprise.identerprise', 'users.client_enterprise_identerprise')
                                    ->where('id', $userId)
                                    ->first();

                if (empty($useridenterprise)) {
                    $failure++;
                    continue;
                }

                $order_connection = Order::on('mysql');

                switch ($idRole) {
                    case Constant::ROLE_ENTERPRISE:
                        $client_userid = $userId;

                        if ($useridenterprise->id_type == Constant::ENTERPRISE_TYPE_REGULAR) {
                            $orderType = Constant::ORDER_TYPE_ENTERPRISE;
                        } else if ($useridenterprise->id_type == Constant::ENTERPRISE_TYPE_PLUS) {
                            $orderType = Constant::ORDER_TYPE_ENTERPRISE_PLUS;
                        } else {
                            $orderType = Constant::ORDER_TYPE_ENTERPRISE;
                        }
                        break;

                    case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                        $orderType = Constant::ORDER_TYPE_ENTERPRISE;
                        break;

                    case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                        $orderType = Constant::ORDER_TYPE_ENTERPRISE_PLUS;
                        if($identerprise == env('CARS24_IDENTERPRISE')){
                            $order_connection = Order::on('cars24');
                        }
                        break;

                    default:
                        throw new ApplicationException("errors.access_denied");
                        break;
                }

                $order = $order_connection->create([
                    'trx_id'                            => $trxId,
                    'task_template_task_template_id'    => $task_template_id,
                    'client_enterprise_identerprise'    => $identerprise,
                    'client_userid'                     => $client_userid,
                    'booking_time'                      => $booking_time,
                    'origin_latitude'                   => $origin_latitude,
                    'origin_longitude'                  => $origin_longitude,
                    'destination_latitude'              => $destination_latitude,
                    'destination_longitude'             => $destination_longitude,
                    'user_fullname'                     => $user_fullname,
                    'user_phonenumber'                  => $user_phonenumber,
                    'client_vehicle_license'            => $client_vehicle_license,
                    'vehicle_brand_id'                  => $vehicle_brand_id ?? "",
                    'vehicle_type'                      => $vehicle_type ?? "",
                    'vehicle_transmission'              => $vehicle_transmission ?? "",
                    'vehicle_owner'                     => $vehicle_owner ?? "",
                    'message'                           => $message ?? "",
                    'order_type_idorder_type'           => $orderType,
                    'order_status'                      => Constant::ORDER_OPEN,
                    'status'                            => Constant::STATUS_ACTIVE,
                    'created_by'                        => auth()->guard('api')->user()->id,
                    'origin_name'                       => $origin_name,
                    'destination_name'                  => $destination_name,
                    'vehicle_year'                      => $vehicle_year ?? 0
                ]);

                // creat or update vehicle type
                $vehicletype = VehicleType::updateOrCreate(
                    [
                        'type_name'        => $vehicle_type,
                        'vehicle_brand_id' => $vehicle_brand_id
                    ],
                    [
                        'type_name'        => $vehicle_type,
                        'vehicle_brand_id' => $vehicle_brand_id
                    ]
                );

                if ($order) {
                    $success++;
                } else {
                    $failure++;
                }
                // notifikasi email for client
                // if ($orderType != Constant::ORDER_TYPE_ONDEMAND) {
                //     $emails = User::whereIn('idrole', [Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER, Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS])
                //             ->where('vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                //             ->get();

                //     if (count($emails)>0) {
                //         $orderan =
                //         [
                //             'greeting' => 'Your Order is',
                //             'line' => [
                //                 'nama'          => $user_fullname,
                //                 'plat nomor'    => $client_vehicle_license,
                //                 'branch'        => $vehicle_brand_id,
                //                 'type'          => $vehicle_type,
                //                 'transmission'  => $vehicle_transmission,
                //                 'origin'        => $origin_latitude.'-'. $origin_longitude,
                //                 'destination'   => $destination_latitude.'-'. $destination_longitude,
                //                 'booking time'  => $booking_time ,
                //                 'message'       => $user_fullname ,
                //                 'order create'  => $useridenterprise->name,
                //             ],
                //         ];
                //         foreach ($emails as $email) {
                //             $email->notify(new OrderNotification($orderan));
                //         }
                //     }
                // }
            }
                Log::info('Success: ' . $success . ' Fail: ' . $failure);

                if ($failure > 0) {
                    Session::put('status_importorder',$report_eror);
                    Session::put('jum_insert', "kosong");
                    DB::rollBack();
                } else {
                    Session::put('status_importorder', "true");
                    Session::put('jum_insert', "ada");
                    DB::commit();
                }

                return [
                    'status'    => true,
                    'success'   => $success,
                    'fail'      => $failure
                ];
        } catch (\Exception $ex) {
            $failure++;
            DB::rollBack();
            Log::error($ex->getMessage());
            Session::put('jum_insert', "kosong");
            Session::put('status_importorder', "Ubah Format Date menjadi text atau YYYY/MM/DD");
            return [
                'status' => false,
                'message' => $ex->getMessage()
            ];
        }
    }
}
