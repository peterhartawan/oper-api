<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Mail\InvoiceMail;
use App\Models\B2C\OrderB2C;
use App\Models\B2C\RatingB2C;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class MailTestController extends Controller
{
    public function getMailFromOrder($ot_order_id){
        // Read data
        $order_ot = Order::where('idorder', $ot_order_id)
            ->with(['driver', 'vehicle_branch'])
            ->first();

        if(empty($order_ot)){
            throw new ApplicationException('orders.not_found');
        }

        $order_b2c = OrderB2C::where('oper_task_order_id', $order_ot->idorder)
            ->where('status', '>=', 4)
            ->where('status', '!=', 6)
            ->with(['customer'])
            ->first();

        if(empty($order_b2c)){
            throw new ApplicationException('orders.not_found');
        }

        // Order time
        $carbon_time_start = Carbon::parse($order_b2c->time_start);
        $carbon_time_end = Carbon::parse($order_b2c->time_end);

        $jam_paket = ($order_b2c->service_type_id == 0 ? 9 : ($order_b2c->service_type_id == 1 ? 12 : 4));
        // dd($jam_paket);
        $carbon_paket_end = Carbon::parse($order_b2c->time_start)->addHours($jam_paket);
        // dd($carbon_paket_end);

        $overtime = $carbon_paket_end->diffInHours($carbon_time_end, false) + 1;
        if($overtime < 0)
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
        $paket_cost = 220000;
        $order_b2c->service_type_id == 1 ? $paket_cost = 270000 : $paket_cost = 135000;
        $insurance_cost = $order_b2c->insurance * 25000;
        $overtime_cost = $overtime * 35000;
        $overall_cost = $paket_cost + $insurance_cost + $overtime_cost;

        $formatted_paket_cost = number_format($paket_cost, 0, ",", ".");

        $formatted_insurance_cost = 0;
        if($insurance_cost > 0)
            $formatted_insurance_cost = number_format($insurance_cost, 0, ",", ".");

        $formatted_overtime_cost = 0;
        if($overtime_cost > 0)
            $formatted_overtime_cost = number_format($overtime_cost, 0, ",", ".");

        $formatted_overall_cost = number_format($overall_cost);

        // return view('mail/invoice', [
        //     "order_ot" => $order_ot,
        //     "order_b2c" => $order_b2c,
        //     // parsed time_start
        //     "parsed_time_start" => $carbon_time_start->format('H.i - d F Y'),
        //     // parsed time_end
        //     "parsed_time_end" => $carbon_time_end->format('H.i - d F Y'),
        //     // calculated overtime
        //     "overtime" => $overtime,
        //     "elapsed_time" => $elapsed_time,
        //     "rating" => $rating,
        //     "paket_cost" => $formatted_paket_cost,
        //     "insurance_cost" => $formatted_insurance_cost,
        //     "overtime_cost" => $formatted_overtime_cost,
        //     "overall_cost" => $formatted_overall_cost
        // ]);
        $mail = new MyMail($order_ot, $order_b2c, $carbon_time_start->format('H.i - d F Y'), $carbon_time_end->format('H.i - d F Y'), $overtime, $elapsed_time, $rating, $formatted_paket_cost, $formatted_insurance_cost, $formatted_overtime_cost, $formatted_overall_cost);

        $invoiceMail = new InvoiceMail($mail);

        Mail::to("ariflukman.asp@gmail.com")->send($invoiceMail);
        return $invoiceMail;
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

    public function __construct($order_ot, $order_b2c, $parsed_time_start, $parsed_time_end, $overtime, $elapsed_time, $rating, $paket_cost, $insurance_cost, $overtime_cost, $overall_cost)
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
    }
}
