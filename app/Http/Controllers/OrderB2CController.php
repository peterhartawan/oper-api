<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\OrderB2C;
use App\Models\Order;
use App\Services\Response;
use App\Services\Validate;
use Illuminate\Http\Request;

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
        $detail = $order_b2c->first();
        return Response::success($detail);
    }

    public function getLatest($phone){
        $customer_id = CustomerB2C::where('phone', $phone)->first()->id;

        $latestOrderB2C = OrderB2C::latest('id')
            ->where('customer_id', $customer_id)
            ->whereNotIn('status', [5,6])
            ->first();

        if(empty($latestOrderB2C))
            throw new ApplicationException("orders.not_found");

        return Response::success($latestOrderB2C);
    }

    public function getFormData($phone){
        $customer_id = CustomerB2C::where('phone', $phone)->first()->id;

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

        $latestOrder = [
            'insurance' => $latestOrderB2C->insurance,
            'local_city' => $latestOrderB2C->local_city,
            'notes' => $latestOrderB2C->notes,
            'service_type_id' => $latestOrderB2C->service_type_id,
            'stay' => $latestOrderB2C->stay,
            'vehicle_brand_id' => $latestOrderOT->vehicle_brand_id,
            'vehicle_type' => $latestOrderOT->vehicle_type,
            'client_vehicle_license' => $latestOrderOT->client_vehicle_license,
            'destination_name' => $latestOrderOT->destination_name,
            'destination_latitude' => $latestOrderOT->destination_latitude,
            'destination_longitude' => $latestOrderOT->destination_longitude,
            'booking_time' => strval($latestOrderOT->booking_time)
        ];

        return Response::success($latestOrder);
    }
}
