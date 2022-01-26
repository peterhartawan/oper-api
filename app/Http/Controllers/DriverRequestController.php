<?php

namespace App\Http\Controllers;

use App\Models\ClientEnterprise;
use App\Models\DriverRequest;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use DB;

class DriverRequestController extends Controller
{
    const ORDER_BY_FIELD = ['enterprise_id', 'place_id', 'number_of_drivers', 'status', 'purpose_time'];

    const STATUS_CANCELED = 'CANCELED';
    const STATUS_REQUESTED = 'REQUESTED';
    const STATUS_FILLED = 'FILLED';
    const STATUS = [
        self::STATUS_CANCELED => -1,
        self::STATUS_REQUESTED => 1,
        self::STATUS_FILLED => 2,
    ];

    /**
     * Get Driver Request list
     *
     * @param [int] enterprise_id
     * @param [int] place_id
     * @param [int] number_of_drivers
     * @param [date] purpose_time
     * @param [int] status
     * @param [string] order_by
     * @param [string] order_type
     * @param [int] size
     * @param [int] page
     * @return [json] DriverRequest object
     */
    public function index(Request $request)
    {
        $user = auth()->guard('api')->user();
        $request->validate([
            'number_of_drivers' => 'sometimes|numeric|min:1',
            'purpose_time' => 'sometimes',
            'status' => 'sometimes|in:' . implode(',', self::STATUS),
            'order_by' => 'sometimes|in:' . implode(',', self::ORDER_BY_FIELD),
            'order_type' => 'sometimes|in:asc,desc',
            'limit' => 'sometimes|numeric|min:1',
            'page' => 'sometimes|numeric|min:1',
        ]);

        // Query param
        $enterprise_id = $request->query('enterprise_id');
        $place_id = $request->query('place_id');
        $number_of_drivers = $request->query('number_of_drivers');
        $purpose_time = $request->query('purpose_time');
        $status = $request->query('status');
        $order_by = $request->has('order_by') ? $request->query('order_by') : 'id';
        $order_type = $request->has('order_type') ? $request->query('order_type') : 'asc';
        $limit = $request->has('size') ? (int)$request->query('size') : Constant::LIMIT_PAGINATION;

        $data = DriverRequest::select(
            'id',
            'enterprise_id',
            'place_id',
            'number_of_drivers',
            'note',
            'purpose_time',
            'status'
        )->orderBy($order_by, $order_type);

        if ($user->idrole == Constant::ROLE_VENDOR) {
            $vendorEnterprises = $user->vendor->enterprises;
            $vendorEnterprisesIds = [];
            foreach ($vendorEnterprises as $k => $v) {
                array_push($vendorEnterprisesIds, $v->identerprise);
            }
            $data = $data->wherein('enterprise_id', $vendorEnterprisesIds);

            if (!empty($enterprise_id)) {
                $data = $data->where('enterprise_id', '=', $enterprise_id);
            }
        } else if ($user->idrole != Constant::ROLE_VENDOR) {
            $data = $data->where('enterprise_id', '=', $user->client_enterprise_identerprise);
        }

        if (!empty($place_id)) {
            $data = $data->where('place_id', '=', $place_id);
        }
        if (!empty($number_of_drivers)) {
            $data = $data->where('number_of_drivers', '=', $number_of_drivers);
        }
        if (!empty($purpose_time)) {
            $data = $data->where('purpose_time', 'like', '%' . $purpose_time . '%');
        }
        if (!empty($status)) {
            $data = $data->where('status', '=', $status);
        }

        $data = $data->with('enterprise', 'place')->paginate($limit);

        return Response::success($data);
    }

    /**
     * Get Client enterprise detail
     *
     * @param [int] id
     * @return [json] DriverRequest object
     */
    public function show($id)
    {
        return Response::success(DriverRequest::where('id', $id)->with('enterprise', 'place')->first());
    }

    /**
     * Create Driver Request
     *
     * @param [int] enterprise_id from client_enterprise table
     * @param [int] place_id from places table
     * @param [string] note
     * @param [date] purpose_time
     */
    public function store(Request $request)
    {
        $request->validate([
            'enterprise_id' => 'required|exists:client_enterprise,identerprise',
            'place_id' => 'required|exists:places,idplaces',
            'number_of_drivers' => 'required|numeric|min:1',
            'note' => 'required',
            'purpose_time' => 'required|date_format:Y-m-d H:i:s',
        ]);
        $user = auth()->guard('api')->user();

        DB::beginTransaction();
        try {
            $data = DriverRequest::create([
                'enterprise_id' => $request->get('enterprise_id'),
                'place_id' => $request->get('place_id'),
                'number_of_drivers' => $request->get('number_of_drivers'),
                'note' => $request->get('note'),
                'purpose_time' => $request->get('purpose_time'),
                'status' => self::STATUS[self::STATUS_REQUESTED],
                'requested_by' => $user->id
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("driver_requests.failure_save_driver_request");
        }

        return Response::success($data);
    }

    /**
     * Create Driver Request
     *
     * @param [int] enterprise_id from client_enterprise table
     * @param [int] place_id from places table
     * @param [string] note
     * @param [date] purpose_time
     */
    public function update($id, Request $request)
    {
        $request['id'] = $id;
        $request->validate([
            'id' => 'required|exists:driver_requests',
            'enterprise_id' => 'required|exists:client_enterprise,identerprise',
            'place_id' => 'required|exists:places,idplaces',
            'number_of_drivers' => 'required|numeric|min:1',
            'note' => 'required',
            'purpose_time' => 'required|date_format:Y-m-d H:i:s',
        ]);
        $user = auth()->guard('api')->user();

        DB::beginTransaction();
        try {
            DriverRequest::where('id', '=', $id)
                ->update([
                    'enterprise_id' => $request->get('enterprise_id'),
                    'place_id' => $request->get('place_id'),
                    'number_of_drivers' => $request->get('number_of_drivers'),
                    'note' => $request->get('note'),
                    'purpose_time' => $request->get('purpose_time'),
                    'status' => self::STATUS[self::STATUS_REQUESTED],
                    'requested_by' => $user->id
                ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("driver_requests.failure_save_driver_request");
        }

        $data = DriverRequest::where('id', '=', $id)->first();

        return Response::success($data);
    }
}
