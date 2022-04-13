<?php

namespace App\Http\Helpers;

class Notification
{
    public static function generateNotification($fcmToken,$title,$messagebody,$type)
    {
        $message = array(
            "registration_ids"=>$fcmToken,
            "notification"=>array(
                "title"=>$title,
                "body"=>$messagebody,
                "priority"=>"high"
            ),
            "data"=>array(
                "notification_type" => $type,
                "title"=>$title,
                "body"=>$messagebody
            ),
            "priority"=>10
        );
        return json_encode($message);
    }

    public static function sendNotification($message){
        $firebaseKey="key=".env("FIREBASE_KEY");
        $firebaseUrl=env("FIREBASE_URL");
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "{$firebaseUrl}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "{$message}",
            CURLOPT_HTTPHEADER => array(
                "Authorization: {$firebaseKey}",
                "Cache-Control: no-cache",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return false;
        } else {
            return $response;
        }

    }

}
