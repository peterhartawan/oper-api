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
            'rating'        => 'int|required',
            'comment'       => "string",
        ]);

        //do the insert
        $rating_b2c_data = [
            'b2c_order_id'  => $request->b2c_order_id,
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
            throw new ApplicationException("orders.failure_create_order");
        }
    }
}
