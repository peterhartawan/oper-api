<?php

return [
    'failure_save_employee_position' => [
        'code'          => 42601,
        'status_code'   => 422,
        'message'       => "Unable to save employee position. Please try again."],
    'failure_delete_employee_position' => [
        'code'          => 42602,
        'status_code'   => 422,
        'message'       => "Unable to delete employee position with ID :id. Please try again."],
    'employee_position_not_found' => [
        'code'          => 42603,
        'status_code'   => 422,
        'message'       => "Unable to find employee position with id employee position :id. Please try again."],
];