<?php

return [
    'failure_save_attendance' => [
        'code'          => 40100,
        'status_code'   => 422,
        'message'       => "Unable to save attendance. Please try again."],
    'failure_clockin' => [
        'code'          => 40101,
        'status_code'   => 422,
        'message'       => "Unable to clock in, check your last clock out"],
    'failure_already_clockin' => [
        'code'          => 40102,
        'status_code'   => 422,
        'message'       => "Unable to clock in, Already clock in for today."],
    'failure_already_clockout' => [
        'code'          => 40103,
        'status_code'   => 422,
        'message'       => "Unable to clock out, Already clock out for today."],
    'failure_not_yet_clock_in' => [
        'code'          => 40104,
        'status_code'   => 422,
        'message'       => "Unable to clock out, Not yet clock in for today."],
    'failure_attendance_task_clockin' => [
        'code'          => 40105,
        'status_code'   => 422,
        'message'       => "Unable to order task, Not yet clock in for today."],
    'failure_open_order' => [
        'code'          => 40105,
        'status_code'   => 422,
        'message'       => "Unable to check order, Please clock in."],
    'failure_clock_in' => [
        'code'          => 40105,
        'status_code'   => 423,
        'message'       => "Unable to clock in, check your location."],
    'failure_clock_in_setting_lock' => [
        'code'          => 40106,
        'status_code'   => 423,
        'message'       => "Unable to clock in, check setting location driver."],
];