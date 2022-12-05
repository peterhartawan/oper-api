<?php

namespace App\Http\Controllers;

use App\Exceptions\ApplicationException;
use App\Models\Order;
use App\Services\PolisHandler;
use App\Services\Response;
use App\Services\Validate;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PolisController extends Controller{

    public function submitPolisB2B(Request $request){
        Validate::request($request->all(), [
            'idorder'   => 'required|integer'
        ]);

        $order = Order::where('idorder', $request->idorder)->first();

        if(empty($order)){
            throw new ApplicationException("orders.not_found");
        }

        // Submit Insurance
        $polisHandler = new PolisHandler();
        $insuranceResponse = $polisHandler->submitOrderB2B($order);

        // Insurance Submitted
        if($insuranceResponse->status == "200"){
            Order::where('idorder', $request->idorder)->update([
                "polis_link" => $insuranceResponse->certificate_url
            ]);
        }

        return Response::success($order);
    }
}
