<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\Order;
use App\Services\PolisHandler;
use App\Services\Response;
use App\Services\Validate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PolisController extends Controller{

    public function submitPolisB2B(Request $request){
        Validate::request($request->all(), [
            'idorder'   => 'required|integer'
        ]);

        $order = Order::where('idorder', $request->idorder)->first();

        if(empty($order)){
            throw new ApplicationException("orders.not_found");
        }

        $insuranceOrder = [
            "trx_id" => $order->trx_id,
            "task_template_id" => $order->task_template_task_template_id,
            "booking_start" => Carbon::parse($order->booking_time)->format('Y-m-d H:i'),
            "driver_name" => $order->driver->user->name,
            "client_vehicle_license" => $order->client_vehicle_license,
            "user_fullname" => $order->user_fullname,
            "user_phonenumber" => $order->user_phonenumber,
            "vehicle_owner" => $order->vehicle_owner,
            "vehicle_brand_id" => $order->vehicle_branch->brand_name,
            "vehicle_type" => $order->vehicle_type,
            "vehicle_year" => $order->vehicle_year,
            "vehicle_transmission" => $order->vehicle_transmission,
            "message" => $order->message,
            "origin_latitude" => $order->origin_latitude,
            "origin_longitude" => $order->origin_longitude,
            "origin_name" => $order->origin_name,
            "destination_latitude" => $order->destination_latitude,
            "destination_longitude" => $order->destination_longitude,
            "destination_name" => $order->destination_name,
        ];

        // Submit Insurance
        $polisHandler = new PolisHandler();
        $insuranceResponse = $polisHandler->submitOrderB2B($insuranceOrder);

        // Insurance Submitted
        if($insuranceResponse->status == "200"){
            Order::where('idorder', $request->idorder)->update([
                "polis_link" => $insuranceResponse->certificate_url
            ]);
        }

        return Response::success($order);
    }
}
