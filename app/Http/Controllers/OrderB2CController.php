<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Models\B2C\ApplyOrderB2C;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\Kupon;
use App\Models\B2C\OrderB2C;
use App\Models\B2C\RatingB2C;
use App\Models\Order;
use App\Models\VehicleBrand;
use App\Services\QontakHandler;
use DB;
use App\Services\Response;
use App\Services\Validate;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderB2CController extends Controller
{
    /**
     * For drivers applying to current order
     * @param [Request] request
     * @return [json]
     */
    public function apply(Request $request)
    {
        Validate::request($request->all(), [
            'link' => 'required|string:40',
            'phone' => 'required|string'
        ]);

        $user = User::where('phonenumber', $request->phone)
            ->first();

        $driver_id = $user->id;

        $apply_order = ApplyOrderB2C::where('link', $request->link)
            ->count();

        // Validate apply count
        if ($apply_order >= 3) {
            return Response::success(3);
        }

        $sequence = $apply_order + 1;

        DB::beginTransaction();
        try {
            ApplyOrderB2C::create([
                'link' => $request->link,
                'driver_userid' => $driver_id,
                'sequence' => $sequence
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return Response::success(3);
        }

        if ($sequence == 1)
            return Response::success(4);
        else
            return Response::success(2);
    }

    public function checkApply(Request $request)
    {
        Validate::request($request->all(), [
            'link' => 'required|string:40',
            'phone' => 'required|string'
        ]);

        // 2 = waiting list
        // 3 = full
        // 4 = empty
        // 5 = already in first
        // 6 = already in wait

        $user = User::where('phonenumber', $request->phone)
            ->first();

        $driver_id = $user->id;

        // Validate is iddriver exist
        $apply_order = ApplyOrderB2C::where('driver_userid', $driver_id)
            ->where('link', $request->link)
            ->first();
        $apply_count = ApplyOrderB2C::where('driver_userid', $driver_id)
            ->where('link', $request->link)
            ->count();

        if ($apply_count > 0) {
            if($apply_order->sequence == 1)
                return Response::success(5);
            else
                return Response::success(6);
        }

        // Validate order count
        $apply_order = ApplyOrderB2C::where('link', $request->link)
            ->count();

        if ($apply_order >= 3) {
            return Response::success(3);
        }

        // Validate apply count
        if ($apply_order >= 1) {
            return Response::success(2);
        }

        return Response::success(4);
    }

    /**
     * Display the specified resource.
     *
     * @param  [int]    id
     * @return [json]   OrderB2C with Order object
     */
    public function showByLink($link)
    {
        $order_b2c = OrderB2C::where('link', $link);
        $detail = $order_b2c->with(['customer', 'paket', 'kupon'])->first();
        return Response::success($detail);
    }

    public function getLatest($phone)
    {
        $customer = CustomerB2C::where('phone', $phone)->first();

        if (empty($customer))
            throw new ApplicationException("customers.not_found");

        $customer_id = $customer->id;

        $latestOrderB2C = OrderB2C::latest('id')
            ->where('customer_id', $customer_id)
            ->where('status', '!=', 6)
            ->first();

        if (empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        return Response::success($latestOrderB2C);
    }

    public function getFormDataByLink($link)
    {
        $latestOrderB2C = OrderB2C::latest('id')
            ->where('link', $link)
            ->with(['customer', 'paket'])
            ->first();

        if (empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        $latestOrderOT = Order::on('mysql')
            ->where('idorder', $latestOrderB2C->oper_task_order_id)
            ->first();

        if (empty($latestOrderOT))
            throw new ApplicationException("orders.not_found");

        $vehicleBrandName = VehicleBrand::where('id', $latestOrderOT->vehicle_brand_id)->first()->brand_name;

        $kupon = null;
        if ($latestOrderB2C->kupon_id != null) {
            $kupon = Kupon::where('id', $latestOrderB2C->kupon_id)
                ->with(['promo'])
                ->first();
        }

        $latestOrder = [
            'customer' => $latestOrderB2C->customer,
            'paket' => $latestOrderB2C->paket,
            'insurance' => $latestOrderB2C->insurance,
            'local_city' => $latestOrderB2C->local_city,
            'notes' => $latestOrderB2C->notes,
            'service_type_id' => $latestOrderB2C->service_type_id,
            'stay' => $latestOrderB2C->stay,
            'vehicle_brand_id' => $latestOrderOT->vehicle_brand_id,
            'vehicle_brand' => $vehicleBrandName,
            'vehicle_type' => $latestOrderOT->vehicle_type,
            'vehicle_year' => $latestOrderOT->vehicle_year,
            'vehicle_transmission' => $latestOrderOT->vehicle_transmission,
            'client_vehicle_license' => $latestOrderOT->client_vehicle_license,
            'destination_name' => $latestOrderOT->destination_name,
            'destination_latitude' => $latestOrderOT->destination_latitude,
            'destination_longitude' => $latestOrderOT->destination_longitude,
            'booking_time' => strval($latestOrderOT->booking_time),
            'kupon' => $kupon,
            'driver_id' => $latestOrderOT->driver_userid
        ];

        return Response::success($latestOrder);
    }

    public function getFormDataByPhone($phone)
    {
        $customer_id = CustomerB2C::where('phone', $phone)->first()->id;

        if (empty($customer_id))
            throw new ApplicationException("customers.not_found");

        $latestOrderB2C = OrderB2C::latest('id')
            ->where('customer_id', $customer_id)
            ->with(['paket'])
            ->first();

        if (empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        $latestOrderOT = Order::on('mysql')
            ->where('idorder', $latestOrderB2C->oper_task_order_id)
            ->first();

        if (empty($latestOrderOT))
            throw new ApplicationException("orders.not_found");

        $vehicleBrandName = VehicleBrand::where('id', $latestOrderOT->vehicle_brand_id)->first()->brand_name;

        $kupon = null;
        if ($latestOrderB2C->kupon_id != null) {
            $kupon = Kupon::where('id', $latestOrderB2C->kupon_id)
                ->with(['promo'])
                ->first();
        }

        $latestOrder = [
            'insurance' => $latestOrderB2C->insurance,
            'local_city' => $latestOrderB2C->local_city,
            'notes' => $latestOrderB2C->notes,
            'paket' => $latestOrderB2C->paket,
            'stay' => $latestOrderB2C->stay,
            'vehicle_brand_id' => $latestOrderOT->vehicle_brand_id,
            'vehicle_brand' => $vehicleBrandName,
            'vehicle_type' => $latestOrderOT->vehicle_type,
            'vehicle_year' => $latestOrderOT->vehicle_year,
            'vehicle_transmission' => $latestOrderOT->vehicle_transmission,
            'client_vehicle_license' => $latestOrderOT->client_vehicle_license,
            'destination_name' => $latestOrderOT->destination_name,
            'destination_latitude' => $latestOrderOT->destination_latitude,
            'destination_longitude' => $latestOrderOT->destination_longitude,
            'booking_time' => strval($latestOrderOT->booking_time),
            'kupon' => $kupon
        ];

        return Response::success($latestOrder);
    }

    public function cancelOrder(Request $request)
    {
        Validate::request($request->all(), [
            'link'  => 'required'
        ]);

        $link = $request->link;

        $order = OrderB2C::where('link', $link)->first();

        if (empty($order)) {
            throw new ApplicationException("orders.not_found");
        }

        DB::commit();

        try {
            $canceledOrder = OrderB2C::where('link', $link)
                ->update([
                    'status' => 5
                ]);

            return Response::success($canceledOrder);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("ofailure_delete_order");
        }
    }

    public function getInvoiceData(Request $request)
    {
        Validate::request($request->all(), [
            'link'  => 'required'
        ]);

        $link = $request->link;

        // Read data
        $order_b2c = OrderB2C::where('link', $link)
            ->where('status', '>=', 3)
            ->where('status', '!=', 6)
            ->with(['customer', 'paket', 'kupon'])
            ->first();

        $ot_order_id = $order_b2c->oper_task_order_id;
        $order_ot = Order::where('idorder', $ot_order_id)
            ->with(['driver', 'vehicle_branch'])
            ->first();

        if (empty($order_ot)) {
            throw new ApplicationException('orders.not_found');
        }

        // Order time
        $carbon_time_start = Carbon::parse($order_b2c->time_start);
        $carbon_time_end = Carbon::parse($order_b2c->time_end);

        $jam_paket = $order_b2c->paket->jumlah_jam;
        $carbon_paket_end = Carbon::parse($order_b2c->time_start)->addHours($jam_paket);

        $overtime = $carbon_paket_end->diffInHours($carbon_time_end, false) + 1;
        if ($carbon_time_end->lt($carbon_paket_end))
            $overtime = 0;

        $elapsed_interval = $carbon_time_end->diff($carbon_time_start);
        $hours = ($elapsed_interval->d * 24) + $elapsed_interval->h;
        $elapsed_time = $elapsed_interval->format(':%I:%S');
        $elapsed_time = $hours . $elapsed_time;

        // Rating
        $rating = RatingB2C::where('driver_id', $order_ot->driver->iddriver)->avg('rating');

        if (empty($rating)) {
            $rating = 0;
        }

        // Currency Formatting
        $paket_cost = $order_b2c->paket->pricing->harga;

        // Luar Kota
        $order_b2c->local_city == 1 ?
            $intercity_cost = 0 :       //Dalam Kota
            ($order_b2c->stay == 1 ?    //Luar Kota
                $intercity_cost = 200000 :  // Menginap
                $intercity_cost = 120000);  // PP

        // Overtime
        $overtime_cost = $overtime * 30000;

        // Kupon
        $cost_no_kupon = $paket_cost + $intercity_cost + $overtime_cost;
        $overall_cost = $order_b2c->kupon != null ?
            $cost_no_kupon - $order_b2c->kupon->promo->potongan_fixed :
            $cost_no_kupon;

        $formatted_paket_cost = number_format($paket_cost, 0, ",", ".");

        $formatted_intercity_cost = "";
        if ($order_b2c->local_city == 0)
            $formatted_intercity_cost = number_format($intercity_cost, 0, ",", ".");

        $formatted_kupon_cost = $order_b2c->kupon != null ? number_format($order_b2c->kupon->promo->potongan_fixed) : "";

        $formatted_overtime_cost = 0;
        if ($overtime_cost > 0)
            $formatted_overtime_cost = number_format($overtime_cost, 0, ",", ".");

        $formatted_overall_cost = number_format($overall_cost);

        // Driver photo
        if (!empty($order_ot->driver->user->profile_picture)) {
            $order_ot->profile_picture = env('BASE_API') . Storage::url($order_ot->driver->user->profile_picture);
        }

        $mail = new MyMail($order_ot, $order_b2c, $carbon_time_start->format('H.i - d F Y'), $carbon_time_end->format('H.i - d F Y'), $overtime, $elapsed_time, $rating, $formatted_paket_cost, $formatted_intercity_cost, $formatted_overtime_cost, $formatted_overall_cost, $formatted_kupon_cost);

        return Response::success($mail);
    }

    public function beginTracking(Request $request)
    {
        Validate::request($request->all(), [
            'id'  => 'required|int'
        ]);

        $customerPhone = Order::where('idorder', $request->id)
            ->first()->user_phonenumber;

        if (empty($customerPhone)) {
            throw new ApplicationException('orders.not_found');
        }

        $qontakHandler = new QontakHandler();
        $qontakHandler->sendMessage(
            "62" . $customerPhone,
            "Driver Start Tracking",
            Constant::QONTAK_TEMPLATE_DRIVER_START_TRACKING,
            []
        );

        return Response::success();
    }

    public function arrived(Request $request)
    {
        Validate::request($request->all(), [
            'id'  => 'required|int'
        ]);

        $customerPhone = Order::where('idorder', $request->id)
            ->first()->user_phonenumber;

        if (empty($customerPhone)) {
            throw new ApplicationException('orders.not_found');
        }

        $qontakHandler = new QontakHandler();
        $qontakHandler->sendMessage(
            "62" . $customerPhone,
            "Driver Arrived",
            Constant::QONTAK_TEMPLATE_DRIVER_ARRIVED,
            []
        );

        return Response::success();
    }
}

class MyMail
{
    public $order_ot;
    public $order_b2c;
    public $parsed_time_start;
    public $parsed_time_end;
    public $overtime;
    public $elapsed_time;
    public $rating;
    public $paket_cost;
    public $intercity_cost;
    public $overtime_cost;
    public $overall_cost;
    public $kupon_cost;

    public function __construct($order_ot, $order_b2c, $parsed_time_start, $parsed_time_end, $overtime, $elapsed_time, $rating, $paket_cost, $intercity_cost, $overtime_cost, $overall_cost, $kupon_cost)
    {
        $this->order_ot = $order_ot;
        $this->order_b2c = $order_b2c;
        $this->parsed_time_start = $parsed_time_start;
        $this->parsed_time_end = $parsed_time_end;
        $this->overtime = $overtime;
        $this->elapsed_time = $elapsed_time;
        $this->rating = $rating;
        $this->paket_cost = $paket_cost;
        $this->intercity_cost = $intercity_cost;
        $this->overtime_cost = $overtime_cost;
        $this->overall_cost = $overall_cost;
        $this->kupon_cost = $kupon_cost;
    }
}
