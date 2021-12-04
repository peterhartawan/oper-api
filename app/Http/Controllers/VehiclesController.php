<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Vehicles;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Http\Helpers\EventLog;

class VehiclesController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $static = Vehicles::where('status', Constant::STATUS_ACTIVE)
                    ->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION);
        return Response::success($static);
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
            'brand'         => 'required|string',
            'type'          => 'required|string',
            'transmission'  => 'required|string',
            'year'          => 'required|string',
            'color'         => 'required|string',
           
        ]);
        try {
            $static = Vehicles::create([
                'brand'         => $request->brand,                
                'type'          => $request->type,
                'transmission'  => $request->transmission, 
                'year'          => $request->year,                 
                'color'         => $request->color,
                'created_by'    => auth()->guard('api')->user()->id
               
            ]); 

            $dataraw = '';
            $reason  = 'Create Vehicle #';
            $trxid   = $static->idvehicles;
            $model   = 'vehicle';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($static);
        } catch (Exception $e) {
            throw new ApplicationException("vehicles.failure_save_vehicles");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Vehicles  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function show(Vehicles $vehicles)
    {
        $vehicles = Vehicles::find($vehicles)->first();
        return Response::success($static);
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
            'brand'         => 'required|string',
            'type'          => 'required|string',
            'transmission'  => 'required|string',
            'year'          => 'required|string',
            'color'         => 'required|string',
        ]);

      
        try {
            $vehicles = Vehicles::where('idvehicles', $id)
                ->update([
                    'brand'         => $request->brand,                
                    'type'          => $request->type,
                    'transmission'  => $request->transmission, 
                    'year'          => $request->year,                 
                    'color'         => $request->color,              
                    'updated_by'    => auth()->guard('api')->user()->id
                ]); 

            $dataraw = '';
            $reason  = 'Update Vehicle #';
            $trxid   = $id;
            $model   = 'vehicle';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("vehicles.failure_save_vehicles");
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
        $vehicles = Vehicles::where('idvehicles', $id)
                    ->where('status',"!=",Constant::STATUS_DELETED)
                    ->update([
                        'status' => Constant::STATUS_DELETED
                    ]);

        if ($vehicles > 0){
            $dataraw = '';
            $reason  = 'Delete Vehicle #';
            $trxid   = $id;
            $model   = 'vehicle';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("vehicles.failure_delete_vehicles", ['id' => $id]);
        }
    }
}
