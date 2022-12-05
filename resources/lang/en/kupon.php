<?php

return [
    'empty' => [
        'code'          => 40410,
        'status_code'   => 404,
        'message'       => "Anda belum memiliki kupon."],
    'not_found' => [
        'code'          => 40411,
        'status_code'   => 404,
        'message'       => "Coupon with given id not found."],
    'already_has_coupon' => [
        'code'          => 42201,
        'status_code'   => 422,
        'message'       => "Already has coupon with given code, cannot claim more."],
    'create_kupon_failed' => [
        'code'          => 42201,
        'status_code'   => 422,
        'message'       => "Create kupon failed."],
];
