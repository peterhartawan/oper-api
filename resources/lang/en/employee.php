<?php

return [
    'failure_save_employee' => [
        'code'          => 42501,
        'status_code'   => 422,
        'message'       => "Unable to save employee. Please try again."],
    'failure_delete_employee' => [
        'code'          => 42502,
        'status_code'   => 422,
        'message'       => "Unable to delete employee with ID :id. Please try again."],
    'employee_not_found' => [
        'code'          => 42503,
        'status_code'   => 422,
        'message'       => "Unable to find employee with User ID :id. Please try again."],
    'employee_full' => [
        'code'          => 42504,
        'status_code'   => 422,
        'message'       => "Sorry there are no employee available at this time."],
    'employee_vendor_not_same' => [
        'code'          => 42505,
        'status_code'   => 422,
        'message'       => "Sorry vendor employee not the same"],
    'failure_assign_order' => [
        'code'          => 42506,
        'status_code'   => 422,
        'message'       => "Unable to assign order. Employee is in order."],
    'failure_assign_order_employee' => [
        'code'          => 42507,
        'status_code'   => 422,
        'message'       => "Unable to assign task employee. Employee is currently not available."],
            
];