<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are the default lines which match reasons
    | that are given by the password broker for a password update attempt
    | has failed, such as for an invalid token or invalid new password.
    |
    */

    'sent' => "We have sent your OTP request.",
    'phone_not_found' => [
        'code'          => 42401,
        'status_code'   => 422,
        'message'       => "We can't find an inspector with phone number :phone."],
    'invalid' => [
        'code'          => 42402,
        'status_code'   => 422,
        'message'       => "Invalid OTP code. Please try again."],
    'expired' => [
        'code'          => 42403,
        'status_code'   => 422,
        'message'       => "OTP expired."],
    'invalid_driver_idordertask' => [
        'code'          => 42404,
        'status_code'   => 422,
        'message'       => "Order task with ID :id is not your task."],
    'empty_client_enterprise' => [
        'code'          => 42405,
        'status_code'   => 422,
        'message'       => "Client is not a member of any enterprise"],
    'inspector_not_registered' => [
        'code'          => 42406,
        'status_code'   => 422,
        'message'       => "Inspector with phone number :phone is not registered to this enterprise."],
    'time_otp_validate' => [
        'code'          => 42407,
        'status_code'   => 422,
        'message'       => "OTP has been sent, wait a few minutes."],
    'invalid_task_status' => [
        'code'          => 42408,
        'status_code'   => 422,
        'message'       => "Could not request otp. Task is not in progress."],
    'task_not_required_otp' => [
        'code'          => 42409,
        'status_code'   => 422,
        'message'       => "Task do not required an OTP validation."],
    'failed_send_otp' => [
        'code'          => 42410,
        'status_code'   => 422,
        'message'       => "failed send OTP."],
    'not_otp_yet'     => [
        'code'          => 42411,
        'status_code'   => 404,
        'message'       => "User hasn't verified OTP yet."]
    ]
];
