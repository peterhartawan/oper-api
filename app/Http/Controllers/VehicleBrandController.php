<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VehicleBrand;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\EventLog;
use App\Http\Helpers\Paginator;

class VehicleBrandController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;
        $name               = $request->query('q');
        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $vehicle_brand_awal = VehicleBrand::select('id','brand_name');

            if(!empty($name)){
                $vehicle_brand_awal = $vehicle_brand_awal->where("vehicle_brand.brand_name","like","%".$name."%");
            }
           $vehicle_brand = $vehicle_brand_awal->paginate($request->query('limit') ?? 100);
        }else{
            $vehicle_brand = VehicleBrand::paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION);
        }

        return Response::success($vehicle_brand);
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
            'brand_name'   => 'required|string',

        ]);

        $brand_name   = strtoupper($request->brand_name);

        try {
            $vehicle_brand = VehicleBrand::create([
                'brand_name'         => $brand_name,
            ]);

            $dataraw = '';
            $reason  = 'Create Vehicle Brand #';
            $trxid   = $vehicle_brand->id;
            $model   = 'vehicle brand';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($vehicle_brand);
        } catch (Exception $e) {
            throw new ApplicationException("vehiclebrand.failure_save_vehicle_brand");
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
        $vehicle_brand = VehicleBrand::find($id)->first();
        return Response::success($vehicle_brand);
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
            'brand_name'   => 'required|string',
        ]);

        $brand_name   = strtoupper($request->brand_name);

        try {
            $vehicles = VehicleBrand::where('id', $id)
                ->update([
                    'brand_name'    => $brand_name
                ]);

            $dataraw = '';
            $reason  = 'Update Vehicle Brand #';
            $trxid   = $vehicles->id;
            $model   = 'vehicle brand';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("vehiclebrand.failure_save_vehicle_brand");
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
        $VehicleBrand = VehicleBrand::destroy($id);

        if ($VehicleBrand > 0){
            $dataraw = '';
            $reason  = 'Update Vehicle Brand #';
            $trxid   = $id;
            $model   = 'vehicle brand';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);
            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("vehiclebrand.failure_delete_vehicle_brand", ['id' => $id]);
        }
    }

}
