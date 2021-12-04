<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Places;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Http\Helpers\EventLog;

class PlacesController extends Controller
{
     /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $keyword_search     = $request->query('q');
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown') : Constant::OPTION_DISABLE ;
        $identerprise       = $request->query('identerprise');
        $limit              = $request->query('limit');

        $Places       = Places::where('places.status', Constant::STATUS_ACTIVE);

        if ($is_dropdown == Constant::OPTION_ENABLE) {
            //hardcode karena FE blm ada dropdown search
            $limit = 500;

            $Places = $Places->select('places.idplaces as value', 'places.idplaces','places.name', 'places.latitude', 'places.longitude', 'places.identerprise');

            if(!empty($keyword_search))
                $Places = $Places->where('places.name',"like","%".$keyword_search."%");
        }else{
            $Places = $Places->select('places.*')
            ->join('client_enterprise', 'client_enterprise.identerprise', '=', 'places.identerprise');

            if(!empty($keyword_search))
                $Places = $Places->where(function($query) use ($keyword_search) {
                    $query->where('places.name',"like","%".$keyword_search."%");
                });
        }

        if(!empty($identerprise)){
            $Places = $Places->where('places.identerprise','=',$identerprise);
        }

        return Response::success($Places->paginate($limit ?? Constant::LIMIT_PAGINATION));
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
     * Create Place
     *
     * @param [string] name
     * @param [string] address
     * @param [decicmal] latitude
     * @param [decimal] longitude
     * @param [integer] identerprise
     * @param [tinyint] places_type
    */
    public function store(Request $request)
    {
        Validate::request($request->all(),[
            'name'              => 'required|min:3|max:45|string',
            'address'           => 'required|string',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'identerprise'      => 'required|numeric|exists:client_enterprise'
        ]);
        try {
            $places = Places::create([
                'name'              => $request->name,                
                'address'           => $request->address,
                'latitude'          => $request->latitude, 
                'longitude'         => $request->longitude,                
                'identerprise'      => $request->identerprise, 
                'created_by'        => auth()->guard('api')->user()->id,
               
            ]); 

            $dataraw = '';
            $reason  = 'Create places #';
            $trxid   = $places->idplaces;
            $model   = 'places';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success($places);
        } catch (Exception $e) {
            throw new ApplicationException("places.failure_save_places");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Places  $places
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $places = Places::where('idplaces',$id)->first();

        return $places;
        if (empty($places))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'places','id' => $id]);

        return Response::success($places);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Places  $places
     * @return \Illuminate\Http\Response
     */
    public function edit(Places $places)
    {
        //
    }

    /**
     * Update Places
     *
     * @param  [string] name
     * @param [string] address
     * @param [decicmal] latitude
     * @param [decimal] longitude     * 
     * @param [integer] identerprise
     * @param [tinyint] places_type
    */
    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'name'              => 'required|min:3|max:45|string',
            'address'           => 'required|string',
            'latitude'          => 'required|numeric',
            'longitude'         => 'required|numeric',
            'identerprise'      => 'required|numeric|exists:client_enterprise'
        ]);

      
        try {
            $places = Places::where('idplaces', $id)
                ->update([
                    'name'              => $request->name,                
                    'address'           => $request->address,
                    'latitude'          => $request->latitude, 
                    'longitude'         => $request->longitude,           
                    'updated_by'        => auth()->guard('api')->user()->id
                ]);         

            $dataraw = '';
            $reason  = 'Update places #';
            $trxid   = $id;
            $model   = 'places';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("places.failure_save_places");
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Places  $places
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $places = Places::where('idplaces', $id)
                    ->where('status',"!=",Constant::STATUS_DELETED)
                    ->update([
                        'status' => Constant::STATUS_DELETED
                    ]);

        if ($places > 0){
            $dataraw = '';
            $reason  = 'delete places #';
            $trxid   = $id;
            $model   = 'places';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("places.failure_delete_places", ['id' => $id]);
        }

    }
}
