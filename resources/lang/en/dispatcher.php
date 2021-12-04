<?php

return [
    'failure_save_dispatcher' => [
        'code'          => 40500,
        'status_code'   => 422,
        'message'       => "Unable to save dispatcher. Please try again."],
    'failure_delete_dispatcher' => [
        'code'          => 40501,
        'status_code'   => 422,
        'message'       => "Unable to delete dispatcher with ID :id. Please try again."],
    'failure_get_dispatcher' => [
        'code'          => 40502,
        'status_code'   => 422,
        'message'       => "Unable to get dispatcher list. Please try again."],
    'failure_get_dispatcher_by_role' => [
        'code'          => 40503,
        'status_code'   => 422,
        'message'       => "Unable to get dispatcher list. Invalid roleid."],
    'failure_assign_dispatcher' => [
        'code'          => 40504,
        'status_code'   => 422,
        'message'       => "Unable assign dispatcher to enterprise."],
    'failure_dispatcher_have_been_assign' => [
        'code'          => 40506,
        'status_code'   => 422,
        'message'       => "Unable assign dispatcher to enterprise. dispatchers is already assigned."],
    'failure_dispatcher_not_regular' => [
        'code'          => 40507,
        'status_code'   => 422,
        'message'       => "Unable assign dispatcher to enterprise. User is not dispatcher regular."],
            
];