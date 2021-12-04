<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use App\Services\Response;
use Validator;

class Validate
{   
    public static function request($request = [], $format = [])
    {     
        $validator = Validator::make($request,$format);
        if ($validator->fails())
            throw new ValidationException($validator);
        
        return TRUE;
    }
}