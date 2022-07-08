<?php

namespace App\Http\Controllers;

use App\Models\B2C\RatingB2C;
use App\Services\Response;
use App\Services\Validate;
use Illuminate\Http\Request;
use DB;
use App\Exceptions\ApplicationException;
use App\Models\B2C\OrderB2C;

class RatingB2CController extends Controller
{
    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'b2c_order_id'  => 'int|required|unique:b2c.rating',
            'driver_id'     => 'int|required',
            'rating'        => 'int|required',
            'comment'       => "string",
        ]);

        //do the insert
        $rating_b2c_data = [
            'b2c_order_id'  => $request->b2c_order_id,
            'driver_id'     => $request->driver_id,
            'rating'        => $request->rating,
            'comment'       => $request->comment,
        ];

        DB::beginTransaction();

        try{
            //create new rating
            $rating_b2c = RatingB2C::create($rating_b2c_data);

            //update order status
            OrderB2C::where('id', $request->b2c_order_id)
                ->update([
                    'status' => 5
                ]);
            DB::commit();

            return Response::success($rating_b2c);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("rating.failure_create_rating");
        }
    }

    public function getRatingByDriverId($driver_id){
        $rating = RatingB2C::where('driver_id', $driver_id)->avg('rating');

        if(empty($rating)){
            throw new ApplicationException("rating.not_found");
        }

        return Response::success($rating);
    }
}
