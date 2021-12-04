<?php

return [
	'failure_save_vendor' => [
        'code'          => 42301,
        'status_code'   => 422,
        'message'       => "Unable to save vendor. Please try again."],
    'failure_delete_vendor' => [
        'code'          => 42302,
        'status_code'   => 422,
        'message'       => "Unable to delete vendor with ID :id. Please try again."],
    'vendor_not_found' => [
        'code'          => 42303,
        'status_code'   => 422,
        'message'       => "Unable to find vendor with ID :id ."],
    'failed_to_suspend' => [
        'code'          => 42304,
        'status_code'   => 422,
        'message'       => "Failed to suspend vendor with ID :id."],
    'failed_to_activate' => [
        'code'          => 42305,
        'status_code'   => 422,
        'message'       => "Failed to activate vendor with ID :id."],
    'failed_to_login_suspend' => [
        'code'          => 423056,
        'status_code'   => 422,
        'message'       => "Your account temporary disabled, please contact your admin oper"],
    'failed_to_login_suspend_2' => [
        'code'          => 423057,
        'status_code'   => 422,
        'message'       => "Your account temporary disabled, please contact your admin vendor"],
        

];