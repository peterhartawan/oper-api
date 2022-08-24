<?php

namespace App\Services;

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

    // Create Insurance
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

    // Finish Order
    public function finishOrderB2C($params){

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


}
