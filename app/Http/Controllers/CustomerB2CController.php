<?php

namespace App\Http\Controllers;

use App\Models\B2C\CustomerB2C;
use Illuminate\Http\Request;
use DB;
use App\Exceptions\ApplicationException;
use App\Services\Response;

class CustomerB2CController extends Controller
{
    public function getCustomerByPhone($phone){
        $customer = CustomerB2C::where('phone', $phone)
            ->first();

        if(empty($customer)){
            throw new ApplicationException("customers.not_found");
        }

        return Response::success($customer);
    }
}
