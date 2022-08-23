<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\Kupon;
use App\Models\B2C\OrderB2C;
use App\Models\B2C\RatingB2C;
use App\Models\Order;
use App\Models\VehicleBrand;
use DB;
use App\Services\Response;
use App\Services\Validate;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderB2CController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param  [int]    id
     * @return [json]   OrderB2C with Order object
     */
    public function showByLink($link)
    {
        $order_b2c = OrderB2C::where('link', $link);
        $detail = $order_b2c->with(['customer', 'kupon'])->first();
        return Response::success($detail);
    }

    public function getLatest($phone){
        $customer = CustomerB2C::where('phone', $phone)->first();

        if(empty($customer))
            throw new ApplicationException("customers.not_found");

        $customer_id = $customer->id;

        $latestOrderB2C = OrderB2C::latest('id')
            ->where('customer_id', $customer_id)
            ->where('status', '!=', 6)
            ->first();

        if(empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        return Response::success($latestOrderB2C);
    }

    public function getFormData($phone){
        $customer_id = CustomerB2C::where('phone', $phone)->first()->id;

        if(empty($customer_id))
            throw new ApplicationException("customers.not_found");

        $latestOrderB2C = OrderB2C::latest('id')
            ->where('customer_id', $customer_id)
            ->first();

        if(empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        $latestOrderOT = Order::on('mysql')
            ->where('idorder', $latestOrderB2C->oper_task_order_id)
            ->first();

        if(empty($latestOrderOT))
            throw new ApplicationException("orders.not_found");

        $vehicleBrandName = VehicleBrand::where('id', $latestOrderOT->vehicle_brand_id)->first()->brand_name;

        $kupon = null;
        if($latestOrderB2C->kupon_id != null){
            $kupon = Kupon::where('id', $latestOrderB2C->kupon_id)
                ->with(['promo'])
                ->first();
        }

        $latestOrder = [
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
            'kupon' => $kupon
        ];

        return Response::success($latestOrder);
    }

    public function cancelOrder(Request $request){
        Validate::request($request->all(), [
            'link'  => 'required'
        ]);

        $link = $request->link;

        $order = OrderB2C::where('link', $link)->first();

        if(empty($order)){
            throw new ApplicationException("orders.not_found");
        }

        DB::commit();

        try{
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

    public function getInvoiceData($link){
        // Read data
        $order_b2c = OrderB2C::where('link', $link)
            ->where('status', '>=', 3)
            ->where('status', '!=', 6)
            ->with(['customer', 'kupon'])
            ->first();

        if(empty($order_b2c)){
            throw new ApplicationException('orders.not_found');
        }

        $ot_order_id = $order_b2c->oper_task_order_id;
        $order_ot = Order::where('idorder', $ot_order_id)
            ->with(['driver', 'vehicle_branch'])
            ->first();

        if(empty($order_ot)){
            throw new ApplicationException('orders.not_found');
        }

        // Order time
        $carbon_time_start = Carbon::parse($order_b2c->time_start);
        $carbon_time_end = Carbon::parse($order_b2c->time_end);

        $jam_paket = ($order_b2c->service_type_id == 0 ? 8 : ($order_b2c->service_type_id == 1 ? 12 : 4));
        // dd($jam_paket);
        $carbon_paket_end = Carbon::parse($order_b2c->time_start)->addHours($jam_paket);
        // dd($carbon_paket_end);

        $overtime = $carbon_paket_end->diffInHours($carbon_time_end, false) + 1;
        if($carbon_time_end->lt($carbon_paket_end))
            $overtime = 0;

        $elapsed_interval = $carbon_time_end->diff($carbon_time_start);
        $hours = ($elapsed_interval->d * 24) + $elapsed_interval->h;
        $elapsed_time = $elapsed_interval->format(':%I:%S');
        $elapsed_time = $hours . $elapsed_time;

        // Rating
        $rating = RatingB2C::where('driver_id', $order_ot->driver->iddriver)->avg('rating');

        if(empty($rating)){
            $rating = 0;
        }

        // Currency Formatting
        $order_b2c->service_type_id == 1 ?
            $paket_cost = 299000 : // 12 Jam
            ($order_b2c->service_type_id == 2 ?
                $paket_cost = 185000 : //4 Jam
                $paket_cost = 249000); //8 Jam;

        // Insurance
        $insurance_cost = $order_b2c->insurance * 25000;

        // Overtime
        $overtime_cost = $overtime * 30000;

        // Kupon
        $cost_no_kupon = $paket_cost + $insurance_cost + $overtime_cost;
        $overall_cost = $order_b2c->kupon != null ? $cost_no_kupon - $order_b2c->kupon->promo->potongan_fixed : $cost_no_kupon;

        $formatted_paket_cost = number_format($paket_cost, 0, ",", ".");

        $formatted_kupon_cost = $order_b2c->kupon != null ? number_format($order_b2c->kupon->promo->potongan_fixed) : "";

        $formatted_insurance_cost = 0;
        if($insurance_cost > 0)
            $formatted_insurance_cost = number_format($insurance_cost, 0, ",", ".");

        $formatted_overtime_cost = 0;
        if($overtime_cost > 0)
            $formatted_overtime_cost = number_format($overtime_cost, 0, ",", ".");

        $formatted_overall_cost = number_format($overall_cost);

        // Driver photo
        if (!empty($order_ot->driver->user->profile_picture)) {
            $order_ot->profile_picture = env('BASE_API') . Storage::url($order_ot->driver->user->profile_picture);
        }

        $mail = new MyMail($order_ot, $order_b2c, $carbon_time_start->format('H.i - d F Y'), $carbon_time_end->format('H.i - d F Y'), $overtime, $elapsed_time, $rating, $formatted_paket_cost, $formatted_insurance_cost, $formatted_overtime_cost, $formatted_overall_cost, $formatted_kupon_cost);

        return Response::success($mail);
    }
}

class MyMail {
    public $order_ot;
    public $order_b2c;
    public $parsed_time_start;
    public $parsed_time_end;
    public $overtime;
    public $elapsed_time;
    public $rating;
    public $paket_cost;
    public $insurance_cost;
    public $overtime_cost;
    public $overall_cost;
    public $kupon_cost;

    public function __construct($order_ot, $order_b2c, $parsed_time_start, $parsed_time_end, $overtime, $elapsed_time, $rating, $paket_cost, $insurance_cost, $overtime_cost, $overall_cost, $kupon_cost)
    {
        $this->order_ot = $order_ot;
        $this->order_b2c = $order_b2c;
        $this->parsed_time_start = $parsed_time_start;
        $this->parsed_time_end = $parsed_time_end;
        $this->overtime = $overtime;
        $this->elapsed_time = $elapsed_time;
        $this->rating = $rating;
        $this->paket_cost = $paket_cost;
        $this->insurance_cost = $insurance_cost;
        $this->overtime_cost = $overtime_cost;
        $this->overall_cost = $overall_cost;
        $this->kupon_cost = $kupon_cost;
    }
}
