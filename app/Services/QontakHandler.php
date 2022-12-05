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

class QontakHandler extends ApiHandler
{

    /**
     * sendMessage
     * A service to send message to client
     *
     * @param message (string) (mandatory)
     *
     * @return BaseResponse => mixed json
     */
    public function sendMessage($phone, $title, $template_id, $body)
    {

        return $this->request(
            "POST",
            env('QONTAK_BASE_URL') . "/api/open/v1/broadcasts/whatsapp/direct",
            [
                "to_number" => $phone,
                "to_name" => $title,
                "message_template_id" => $template_id,
                "channel_integration_id" => "379e821d-b4e8-42b8-b630-088a2dcc5431",
                "language" => [
                    "code" => "en"
                ],
                "parameters" => [
                    "body" => $body
                ]
            ],
            "Bearer " . env('QONTAK_API_KEY')
        );
    }

    /**
     * sendMessage
     * A service to send message to client
     *
     * @param message (string) (mandatory)
     *
     * @return BaseResponse => mixed json
     */
    public function sendImageMessage($phone, $title, $template_id, $imageName, $imageUrl, $body)
    {

        return $this->request(
            "POST",
            env('QONTAK_BASE_URL') . "/api/open/v1/broadcasts/whatsapp/direct",
            [
                "to_number" => $phone,
                "to_name" => $title,
                "message_template_id" => $template_id,
                "channel_integration_id" => "379e821d-b4e8-42b8-b630-088a2dcc5431",
                "language" => [
                    "code" => "en"
                ],
                "parameters" => [
                    "header" => [
                        "format" => "IMAGE",
                        "params" => [
                            [
                                "key" => "url",
                                "value" => $imageUrl
                            ],
                            [
                                "key" => "filename",
                                "value" => $imageName
                            ]
                        ]
                    ],
                    "body" => $body
                ]
            ],
            "Bearer " . env('QONTAK_API_KEY')
        );
    }
}
