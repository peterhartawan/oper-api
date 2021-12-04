<?php

return [
    'failure_save_client_enterprise' => [
        'code'          => 40400,
        'status_code'   => 422,
        'message'       => "Unable to save client enterprise. Please try again."],
    'failure_delete_client_enterprise' => [
        'code'          => 40401,
        'status_code'   => 422,
        'message'       => "Unable to delete client enterprise with ID :id. Please try again."],
    'failed_to_suspend' => [
        'code'          => 40403,
        'status_code'   => 422,
        'message'       => "Failed to suspend client with ID :id."],
    'failed_to_activate' => [
        'code'          => 40403,
        'status_code'   => 422,
        'message'       => "Failed to activate client with ID :id."],
    'failed_to_login_suspend' => [
        'code'          => 40404,
        'status_code'   => 422,
        'message'       => "Your client enterprise account has been disabled. Please contact your admin vendor"],
    'test' => [
        'code'          => 40404,
        'status_code'   => 422,
        'message'       => "1"],

];
