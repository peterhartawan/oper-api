<?php

return [
    'failure_save_request' => [
        'code'          => 40100,
        'status_code'   => 422,
        'message'       => "Unable to make attendance request. Please try again."],
    'failure_already_requested' => [
        'code'          => 40101,
        'status_code'   => 422,
        'message'       => "Attendance already requested. Please wait for approval."],
    'failure_already_approved' => [
        'code'          => 40102,
        'status_code'   => 422,
        'message'       => "Attendance already approved."],
    'failure_cancel' => [
        'code'          => 40103,
        'status_code'   => 422,
        'message'       => "Attendance cancel failed."],
];
