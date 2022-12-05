<?php

return [
    'failure_save_driver' => [
        'code'          => 40600,
        'status_code'   => 422,
        'message'       => "Unable to save driver. Please try again."
    ],
    'failure_delete_driver' => [
        'code'          => 40601,
        'status_code'   => 422,
        'message'       => "Unable to delete driver with ID :id. Please try again."
    ],
    'driver_not_found' => [
        'code'          => 40602,
        'status_code'   => 422,
        'message'       => "Unable to find driver with User ID :id. Please try again."
    ],
    'driver_full' => [
        'code'          => 40603,
        'status_code'   => 422,
        'message'       => "Sorry there are no drivers available at this time."
    ],
    'driver_with_phone_not_found' => [
        'code'          => 40604,
        'status_code'   => 422,
        'message'       => "Unable to find driver with Phone Number :phone. Please try again."
    ],
];
