<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\Kupon;
use App\Models\B2C\Promo;
use App\Services\Response;
use App\Services\Validate;
use Illuminate\Http\Request;

class KuponController extends Controller
{
    public function claim(Request $request){
        Validate::request($request->all(), [
            'customer_id' => 'required|int',
            'kode' => 'required|string',
        ]);

        // Check if kode kupon exists on promo table
        $promo = Promo::where('kode', $request->kode)->first();

        if(empty($promo)){
            throw new ApplicationException('promo.not_found');
        }

        return Response::success($promo);
    }
}
