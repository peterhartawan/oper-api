<?php

return [
    'failure_save_trackingtask' => [
        'code'          => 40600,
        'status_code'   => 422,
        'message'       => "Unable to save tracking task. Please try again."],
    'failure_delete_driver' => [
        'code'          => 40601,
        'status_code'   => 422,
        'message'       => "Unable to delete tracking task with ID :id. Please try again."],
    'is_b2c'    => [
        'code'          => 40602,
        'status_code'   => 401,
        'message'       => "Unable to autotrack. Is a b2c driver."],
];
