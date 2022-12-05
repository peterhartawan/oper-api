<?php

namespace App\Http\Controllers;

use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Models\Order;
use App\Services\Response;
use App\Services\Validate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OtopickupController extends Controller
{
    // Tracking otopickup
    public function tracking($trx_id){
        $user = auth()->guard('api')->user();
        $order = Order::with(["order_tasks", "vehicle_branch"])->where('trx_id', $trx_id);

        if($user->client_enterprise_identerprise != env('OP_IDENTERPRISE')){
            throw new ApplicationException('errors.unauthorized');
        }

        $order->where('order.client_enterprise_identerprise',$user->client_enterprise_identerprise)
            ->with(['driver', 'enterprise']);

        $detail_order = $order->first();

        $no = 1;

        // State check
        $currentState = Constant::OP_STATE_PICKUP;
        array_walk($detail_order->order_tasks, function (&$v, $k) use ($no, &$currentState) {
            foreach ($v as $item) {
                $item->no = $no;

                // Attachment image
                if (!empty($item->attachment_url)){
                    $item->attachment_url = env('BASE_API') . Storage::url($item->attachment_url);
                }

                if($item->order_task_status === 2){
                    // if ($item->sequence >= Constant::OP_CONSULT_SEQUENCE){
                    //     $currentState = Constant::OP_STATE_CONSULT;
                    // }
                    if ($item->sequence >= Constant::OP_SERVICE_SEQUENCE){
                        $currentState = Constant::OP_STATE_SERVICE;
                    }
                    if ($item->sequence >= Constant::OP_DROPOFF_SEQUENCE){
                        $currentState = Constant::OP_STATE_DROPOFF;
                    }
                }
            }
            $no++;
        });

        // Add state
        $detail_order->state = $currentState;
        $detail_order->no = $no;

        if (Constant::ORDER_OPEN != $detail_order->order_status) {
            if (!empty($detail_order->driver->user->profile_picture)) {
                $pertama = Storage::url($detail_order->driver->user->profile_picture);
                $detail_order->driver->user->profile_picture = env('BASE_API') . $pertama;
            }
        }

        if ($detail_order->order_status == Constant::ORDER_COMPLETED){
            $detail_order->state = Constant::OP_STATE_FINISH;
        }

        return Response::success($detail_order);
    }
}
