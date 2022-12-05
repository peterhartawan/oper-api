<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Services\PolisHandler;

class TestingController extends Controller
{
    public function __construct()
    {
        if (in_array(env('APP_ENV'), [Constant::ENV_STAGING, Constant::ENV_PRODUCTION])) {
            Response::error("You don't have power here", 4333, 403);
        }
    }

    public function test(Request $request)
    {
        return Response::success();
    }

    // QR TEST
    // public function test(Request $request)
    // {
    //     Validate::request($request->all(), [
    //         'link' => 'required|string',
    //     ]);

    //     $identerprise   = auth()->guard('api')->user()->client_enterprise_identerprise;

    //     try {
    //         //Check B2C
    //         if ($identerprise == env("B2C_IDENTERPRISE")) {
    //             //empty link
    //             $request_link = $request->link;
    //             if (empty($request_link)) {
    //                 throw new ApplicationException("attendance.failure_b2c_empty_link");
    //             }

    //             //query for b2c order & link
    //             $order_b2c = OrderB2C::where('link', $request_link)->with(['paket'])->first();
    //             if (empty($order_b2c)) {
    //                 throw new ApplicationException("attendance.failure_b2c_qr_not_found");
    //             }

    //             $link = $order_b2c->link;
    //             //link mismatch
    //             if ($request_link != $link) {
    //                 throw new ApplicationException("attendance.failure_b2c_qr_mismatch");
    //             }

    //             $order_b2c = OrderB2C::where('link', $request_link)->with(['paket', 'kupon'])->first();

    //             // Get OT Order
    //             $order_ot = Order::where('idorder', $order_b2c->oper_task_order_id)
    //                 ->with(['driver', 'vehicle_branch'])
    //                 ->first();

    //             // Qontak

    //             // Customer
    //             $qontakHandler = new QontakHandler();

    //             // Driver
    //             $driverFullPhone = $order_ot->driver->user->phonenumber;
    //             $driverPhone = substr($driverFullPhone, 1);

    //             // Cost Calculation
    //             // Order time
    //             $carbon_time_start = Carbon::parse($order_b2c->time_start);
    //             $carbon_time_end = Carbon::parse($order_b2c->time_end);

    //             $jam_paket = $order_b2c->paket->jumlah_jam;
    //             $carbon_paket_end = Carbon::parse($order_b2c->time_start)->addHours($jam_paket);

    //             $overtime = $carbon_paket_end->diffInHours($carbon_time_end, false) + 1;
    //             if ($carbon_time_end->lt($carbon_paket_end))
    //                 $overtime = 0;

    //             $elapsed_interval = $carbon_time_end->diff($carbon_time_start);
    //             $hours = ($elapsed_interval->d * 24) + $elapsed_interval->h;
    //             $elapsed_time = $elapsed_interval->format(':%I:%S');
    //             $elapsed_time = $hours . $elapsed_time;

    //             // Currency Formatting
    //             $paket_cost = $order_b2c->paket->pricing->harga;

    //             // Get pricing table
    //             $pricing = Pricing::get();
    //             $lkPP = $pricing[5]->harga;
    //             $lkInap = $pricing[6]->harga;

    //             $lkText = "Luar Kota";

    //             // Luar Kota
    //             if($order_b2c->local_city == 1){
    //                 $intercity_cost = 0;
    //                 $lkText = $lkText . " : Tidak";
    //             } else {
    //                 if($order_b2c->stay == 1){
    //                     $intercity_cost = $lkInap;
    //                     $lkText = $lkText . "(Menginap) : Rp " . number_format($intercity_cost, 0, ",", ".") . ",-";
    //                 } else {
    //                     $intercity_cost = $lkPP;
    //                     $lkText = $lkText . "(PP) : Rp " . number_format($intercity_cost, 0, ",", ".") . ",-";
    //                 }
    //             }

    //             // Overtime
    //             $per_hour = $pricing[0]->harga;
    //             $overtime_cost = $overtime * $per_hour;

    //             $otText = "Overtime ";
    //             if($overtime_cost > 0) {
    //                 $otText = $otText . $overtime . " jam (Rp 30.000/jam): Rp " . number_format($overtime_cost, 0, ",", ".") . ",-";
    //             } else {
    //                 $otText = $otText . ": Tidak";
    //             }

    //             // Kupon
    //             $cost_no_kupon = $paket_cost + $intercity_cost + $overtime_cost;
    //             $promoText = "Kode Promo ";

    //             if($order_b2c->kupon != null){
    //                 $potongan = $order_b2c->kupon->promo->potongan_fixed;
    //                 $kode = $order_b2c->kupon->promo->kode;

    //                 $overall_cost = $cost_no_kupon - $potongan;

    //                 $promoText = $promoText . $kode . " : Potongan Rp " . number_format($potongan, 0, ",", ".") . ",-";
    //             } else {
    //                 $overall_cost =                     $cost_no_kupon;
    //                 $promoText = $promoText . " : Tidak";
    //             }

    //             $formatted_paket_cost = number_format($paket_cost, 0, ",", ".");

    //             $formatted_overall_cost = number_format($overall_cost);

    //             $formatted_booking_time = Carbon::parse($order_ot->booking_time)->format('d/m/Y, h:i') . " WIB";

    //             $qrisBodyMessage = [
    //                 [
    //                     "key" => "1",
    //                     "value" => "nama_driver",
    //                     "value_text" => $order_ot->driver->user->name
    //                 ],
    //                 [
    //                     "key" => "2",
    //                     "value" => "nama_customer",
    //                     "value_text" => $order_ot->user_fullname
    //                 ],
    //                 [
    //                     "key" => "3",
    //                     "value" => "booking_time",
    //                     "value_text" => $formatted_booking_time
    //                 ],
    //                 [
    //                     "key" => "4",
    //                     "value" => "trx_id",
    //                     "value_text" => $order_ot->trx_id
    //                 ],
    //                 [
    //                     "key" => "5",
    //                     "value" => "jumlah_jam",
    //                     "value_text" => $order_b2c->paket->jumlah_jam
    //                 ],
    //                 [
    //                     "key" => "6",
    //                     "value" => "biaya_paket",
    //                     "value_text" => $formatted_paket_cost
    //                 ],
    //                 [
    //                     "key" => "7",
    //                     "value" => "teks_luar_kota",
    //                     "value_text" => $lkText
    //                 ],
    //                 [
    //                     "key" => "8",
    //                     "value" => "teks_overtime",
    //                     "value_text" => $otText
    //                 ],
    //                 [
    //                     "key" => "9",
    //                     "value" => "teks_promo",
    //                     "value_text" => $promoText
    //                 ],
    //                 [
    //                     "key" => "10",
    //                     "value" => "total_biaya",
    //                     "value_text" => $formatted_overall_cost
    //                 ],

    //             ];

    //             $qontakHandler->sendImageMessage(
    //                 "62" . $driverPhone,
    //                 "QRIS",
    //                 Constant::QONTAK_TEMPLATE_QRIS,
    //                 "OPER-QRIS",
    //                 "https://qontak-hub-development.s3.amazonaws.com/uploads/direct/images/31f138f8-8dfb-42c6-9863-871e72e956a1/OPER-QRIS.jpg",
    //                 $qrisBodyMessage
    //             );
    //         }

    //         return Response::success("Test New Qontak Notification Success");
    //     } catch (Exception $e) {
    //         throw new ApplicationException("attendance.failure_save_attendance");
    //     }
    // }
}
