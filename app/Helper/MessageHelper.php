<?php

namespace App\Helper;

use App\Services\TelegramServices;
use App\Services\FonnteServices;
use Illuminate\Support\Facades\Mail;


class MessageHelper{

    /**
     * @static
     * @var string
     *
     * Social Media type identifier.
     */
    const EMAIL = 'email';
    const WHATSAPP = 'whatsapp';

    /**
     * sendMessage
     * A function that help process of sending message to destinated phone number
     *
     * @param string type
     * Either 'email' or 'whatsapp'
     *
     * @param string credential
     * Destinated message. Could be an email, or phone number.
     *
     * @param string message
     * Message string. If you're using email, you must pass view's string Laravel path
     * since we return view for SMTP.
     *
     * @param mixed object (nullable)
     * Just view's parameters if you're using email.
     *
     * @return void
     *
     * @return boolean false if the type is not supported
     */
    public function sendMessage($type, $destination, $message, $object = null, $subject = null){

        switch(strtolower($type)){
            case self::EMAIL:
                $to_name = "Customer {$destination}";
                $to_email = $destination;

                Mail::send($message, $object, function($mes) use ($to_name, $to_email, $subject) {
                    $mes->to($to_email, $to_name)
                        ->subject($subject);

                    $mes->from(env('MAIL_USERNAME'), $subject);
                });

                break;

            case self::WHATSAPP:

                if(env('OTP_MODE') == 'FAKE'){
                    $this->fakeWhatsappMessanging($destination, $message);
                }else{
                    $this->fonnteMessanging($destination, $message);
                }
                break;

            default:
                return false;
                break;
        }

        return 'void';
    }

    /**
     * fakeWhatsappMessanging
     * A function that send message trough Telegram instead of Whatsapp.
     *
     * @param string phone_number
     * @param string message
     */
    private function fakeWhatsappMessanging($phone_number, $message){
        $telegram = new TelegramServices();
        $telegram->sendMessage(
            "Dear {$phone_number},\n\n  {$message}"
        );
    }


    /**
     * fonnteMessanging
     * A function that send message trough Whatsapp.
     *
     * @param string phone_number
     * @param string message
     */
    private function fonnteMessanging($phone_number, $message){
        $fonnte = new FonnteServices();

        $fonnte->sendMessage(
            $phone_number,
            $message
        );
    }
}
