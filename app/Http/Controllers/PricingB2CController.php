<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\Pricing;
use App\Services\Response;

class PricingB2CController extends Controller
{
    /**
     * Returns all pricing data
     */
    public function index(){
        $pricing = Pricing::get();

        if(empty($pricing)){
            throw new ApplicationException("pricing.empty");
        }

        return Response::success($pricing);
    }
}
