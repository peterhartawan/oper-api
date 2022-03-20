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

class FonnteServices extends ApiHandler {

    /**
     * sendMessage
     * A service to send message to client
     * 
     * @param message (string) (mandatory)
     * 
     * @return BaseResponse => mixed json
     */
    public function sendMessage($phone, $text, $type = "text"){

        return $this->request(
            "POST", 
            env('FONNTE_BASE_URL')."/send_message.php", 
            [
                "phone" => $phone,
                "type" => $type,
                "text" => $text
            ],
            env('FONNTE_API_KEY'),
            true
        );
    }
}
