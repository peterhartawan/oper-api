<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\EventLog;

class VehicleTypeController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;
        $idbrand            = $request->query('idbrand');

        $VehicleType        = VehicleType::when($idbrand, function ($query, $search) {
                                return $query->where('vehicle_brand_id', '=', $search);
                            });

        if ($is_dropdown == Constant::OPTION_ENABLE) { 
            $VehicleType = $VehicleType->select('id','type_name')->get();
            return Response::success($VehicleType);
            
        }else{
            return Response::success($VehicleType->paginate( $request->query('limit') ?? Constant::LIMIT_PAGINATION));
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Create Vehicles
     *
     * @param  [string] brand
     * @param  [string] type
     * @param [string] transmission
     * @param [string] year
     * @param [string] color
    */
    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'vehicle_brand_id'  => 'int|required|exists:vehicle_brand,id', 
            'type_name'       => 'required|string',
        ]);

        $type_name   = strtoupper($request->type_name);

        try {
            $VehicleType = VehicleType::create([
                'vehicle_brand_id'   => $request->vehicle_brand_id,
                'type_name'          => $type_name,
            ]); 

            $dataraw = '';
            $reason  = 'Create Vehicle Type #';
            $trxid   = $VehicleType->id;
            $model   = 'vehicle type';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($VehicleType);
        } catch (Exception $e) {
            throw new ApplicationException("vehicletype.failure_save_vehicle_type");
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vehicles  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $VehicleType = VehicleType::find($id)->first();
        return Response::success($VehicleType);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Vehicles  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function edit(Vehicles $vehicles)
    {
        //
    }

    /**
     * Update Vehicles
     *
     * @param  [string] brand
     * @param  [string] type
     * @param [string] transmission
     * @param [string] year
     * @param [string] color
    */
    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'vehicle_brand_id'  => 'int|required|exists:vehicle_brand,id', 
            'type_name'       => 'required|string',
        ]);

        $type_name   = strtoupper($request->type_name);

        try {
            $vehicles = VehicleType::where('id', $id)
                ->update([
                    'vehicle_brand_id'   => $request->vehicle_brand_id,
                    'type_name'         => $type_name
                ]);       

            $dataraw = '';
            $reason  = 'Update Vehicle Type #';
            $trxid   = $id;
            $model   = 'vehicle type';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("vehicletype.failure_save_vehicle_type");
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StaticContent  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $VehicleType = VehicleType::destroy($id); 

        if ($VehicleType > 0){

            $dataraw = '';
            $reason  = 'Delete Vehicle Type #';
            $trxid   = $id;
            $model   = 'vehicle type';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);
            return Response::success(['id' => $id]);

        }else{
            throw new ApplicationException("vehicletype.failure_delete_vehicles", ['id' => $id]);
        }

    }


}
