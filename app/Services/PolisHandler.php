<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7;
use Log;

/**
 * FonnteServices
 * A service to control Whatsapp bot via Fonnte API
 *
 */

class PolisHandler {

    public function checkInsurance($trx_id){
        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = [
            "trx_id" => $trx_id
        ];

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps.ezypolis.com/online-helper/public/api/order/check",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/check \n\n Body: '.json_encode($trx_id));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/check \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($trx_id));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    public function cancelInsurance($trx_id){
        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = [
            "trx_id" => $trx_id
        ];

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps.ezypolis.com/online-helper/public/api/order/cancel",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/cancel \n\n Body: '.json_encode($trx_id));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/cancel \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($trx_id));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    // Create Insurance B2C
    public function submitOrderB2C($order){

        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = $order;

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps.ezypolis.com/online-helper/public/api/b2c/submitOrder",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/b2c/submitOrder \n\n Body: '.json_encode($order));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/b2c/submitOrder \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($order));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    // Create Insurance B2B
    public function submitOrderB2B($order){

        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $insuranceOrder = [
            "trx_id" => $order->trx_id,
            "task_template_id" => $order->task_template_task_template_id,
            "booking_start" => Carbon::now()->format('Y-m-d H:i'),
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

        $options["json"] = $insuranceOrder;

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps.ezypolis.com/online-helper/public/api/b2b/submitOrder",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/b2b/submitOrder \n\n Body: '.json_encode($order));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/b2b/submitOrder \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($order));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    // Create Insurance B2B UAT
    public function submitOrderB2BUAT($order){

        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = $order;

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps-uat.ezypolis.com/online-helper/public/api/b2b/submitOrder",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps-uat.ezypolis.com/online-helper/public/api/b2b/submitOrder \n\n Body: '.json_encode($order));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps-uat.ezypolis.com/online-helper/public/api/b2b/submitOrder \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($order));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    // Finish Order
    public function finishOrder($params){

        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = $params;

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps.ezypolis.com/online-helper/public/api/order/finish",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/finish \n\n Body: '.json_encode($params));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps.ezypolis.com/online-helper/public/api/order/finish \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($params));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }

    // Finish Order UAT
    public function finishOrderUAT($params){

        $client = new Client();
        $options = [];

        $options["headers"] = [];

        $options["headers"] += [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        $options["json"] = $params;

        $options["headers"]["Client-Key"] = "1f4eed77b35cf5b7ca8e8d59902846a7";

        try{
            $response = $client->request(
                "POST",
                "https://apps-uat.ezypolis.com/online-helper/public/api/order/finish",
                $options
            );

            Log::alert('REQUEST_INFO: \n\n URI: https://apps-uat.ezypolis.com/online-helper/public/api/order/finish \n\n Body: '.json_encode($params));
            Log::alert('REQUEST_RESPONSE: '.json_encode((string) $response->getBody()));

            return json_decode((string) $response->getBody());

        }catch(RequestException $e){
            $response = $e->getResponse();

            // Logging error
            Log::alert('REQUEST_INFO: \n\n URI: https://apps-uat.ezypolis.com/online-helper/public/api/order/finish \n\n Headers: '.json_encode($options));
            Log::alert('REQUEST_BODY: '.json_encode($params));
            Log::alert('ERROR_REQUEST: '.Psr7\str($e->getRequest()));
            Log::alert('ERROR_RESPONSE: '.json_encode($e->getResponse()));
            Log::alert('ERROR: '. json_encode($e->getMessage()));

            return json_decode(
                json_encode([
                    "code" => $e->getResponse()->getStatusCode() ?? "",
                    "message" => $e->getResponse()->getReasonPhrase() ?? ""
                ])
            );
        }
    }
}
