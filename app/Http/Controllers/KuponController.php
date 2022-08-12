<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\B2C\Kupon;
use App\Models\B2C\Promo;
use App\Services\Response;
use App\Services\Validate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KuponController extends Controller
{
    public function getKuponById($kupon_id){
        $kupon = Kupon::where('id', $kupon_id)
            ->with(['promo'])
            ->get();

        if($kupon->isEmpty()){
            throw new ApplicationException('kupon.not_found');
        }

        return Response::success($kupon);
    }

    public function getKuponByCustomerId($customer_id){
        $kupon = Kupon::where('customer_id', $customer_id)
            ->with(['promo'])
            ->get();

        if($kupon->isEmpty()){
            throw new ApplicationException('kupon.empty');
        }

        return Response::success($kupon);
    }

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

        // Check if user already have the coupon
        $kuponCount = Kupon::where('promo_id', $promo->id)
            ->where('customer_id', $request->customer_id)
            ->count();

        if($kuponCount > 0){
            throw new ApplicationException('kupon.already_has_coupon');
        }

        // Create new kupon
        try{
            DB::beginTransaction();

            $kupon = Kupon::create(
                [
                    'promo_id' => $promo->id,
                    'customer_id' => $request->customer_id,
                    'jumlah_kupon' => $promo->jumlah_klaim,
                    'waktu_berakhir' => Carbon::today()->addDays($promo->hari_berlaku)->format('Y-m-d')
                ]
            );

            DB::commit();
        } catch (Exception $e){
            DB::rollBack();
            throw new ApplicationException('kupon.create_kupon_failed');
        }

        return Response::success($kupon);
    }
}
