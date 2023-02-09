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
     * @param phone Phone Number (string) (mandatory)
     * @param text Text Message (string) (mandatory)
     *
     * @return BaseResponse => mixed json
     */
    public function sendMessage($phone, $text){

        return $this->request(
            "POST",
            env('FONNTE_BASE_URL')."/send",
            [
                "target"    => $phone,
                "message"   => $text
            ],
            env('FONNTE_API_KEY'),
            true
        );
    }

    /**
     * sendImageMessage
     * A service to send message with image to client
     *
     * @param phone Phone Number (string) (mandatory)
     * @param text Text Message (string) (mandatory)
     * @param url Attachment URL (string) (mandatory)
     *
     * @return BaseResponse => mixed json
     */
    public function sendImageMessage($phone, $text, $url){

        return $this->request(
            "POST",
            env('FONNTE_BASE_URL')."/send",
            [
                "target"    => $phone,
                "message"   => $text,
                "url"       => $url
            ],
            env('FONNTE_API_KEY'),
            true
        );
    }
}
