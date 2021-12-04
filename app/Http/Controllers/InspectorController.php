<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\ClientEnterprise;
use App\Models\Inspector;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\GlobalHelper;
use App\Http\Helpers\EventLog;

class InspectorController extends Controller
{
    /**
     * Get Inspector list
     *
     * @param  [int] limit
     * @param  [int] page
     * @return [json] Inspector object
     */
    public function index(Request $request)
    {   
        $identerprise       = $request->query('identerprise');
        $keyword_search     = $request->query('q');
        $is_dropdown        = $request->query('dropdown') ? $request->query('dropdown'): Constant::OPTION_DISABLE ;

        $inspector = Inspector::where('status','=',Constant::STATUS_ACTIVE);
        
        if ($is_dropdown == Constant::OPTION_ENABLE) {
            $inspector = $inspector->select('inspector.idinspector','inspector.name');

            if(!empty($keyword_search))
                $inspector = $inspector->where("inspector.name","like","%".$keyword_search."%");
        
        } 
        else {
            $inspector = $inspector->with(["enterprise"]);

            if(!empty($identerprise)){
                $inspector = $inspector->where("inspector.client_enterprise_identerprise",$identerprise);
            }

            if(!empty($keyword_search)){
                $inspector  = $inspector->where(function($query) use ($keyword_search) {
                    $query  ->where('inspector.phonenumber','like','%' . $keyword_search . '%')
                            ->orWhere('inspector.name', 'like', '%' . $keyword_search . '%');
                });
            }  
        }

        $inspector->orderBy('idinspector', 'desc');

        return Response::success($inspector->paginate($request->query('limit') ?? Constant::LIMIT_PAGINATION));
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
     * Create Inspector
     *
     * @param  [string] name
     * @param  [string] phone
     * @param  [string] identerprise
    */
    public function store(Request $request)
    {
        
        Validate::request($request->all(),[
            'name'          => 'required|string',
            'phonenumber'   => 'required|string',
            'identerprise'  => 'required|string'
        ]);
        $no_hp      = GlobalHelper::replace_hp($request->phonenumber);
        
        $validateinspector = Inspector::where('phonenumber',$no_hp)
                            ->where('client_enterprise_identerprise',$request->identerprise)->get();
        try {
            if(count($validateinspector) > 0){
                throw new ApplicationException("inspector.failure_save_inspector_");
            }else{
                $inspector = Inspector::create([
                    'client_enterprise_identerprise' => $request->identerprise,
                    'name'          => $request->name,                
                    'phonenumber'   => $no_hp,
                    'status'        => Constant::STATUS_ACTIVE,
                    'created_by'    => auth()->guard('api')->user()->id,
                ]); 

                $dataraw = '';
                $reason  = 'Create Inspector #';
                $trxid   = $inspector->idinspector;
                $model   = 'inspector';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                return Response::success($inspector);
            }
            

        } catch (Exception $e) {
            throw new ApplicationException("inspector.failure_save_inspector");
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Inspector  $inspector
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $inspector = Inspector::where('idinspector', $id)
                    ->where('status',Constant::STATUS_ACTIVE)
                    ->first();

        if (empty($inspector))
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'inspector','id' => $id]);

        return Response::success($inspector);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Inspector  $inspector
     * @return \Illuminate\Http\Response
     */
    public function edit(Inspector $inspector)
    {
        //
    }

    /**
     * Update Inspector
     *
     * @param  [string] name
     * 
     * @param  [string] phonenumber
    */
    public function update(Request $request, $id)
    {
        $inspector = Inspector::where('idinspector', $id)->first();

        if($request->phonenumber == $inspector->phonenumber) {
            $phonenumber ='required|string';
        }else{
            $phonenumber ='required|string|unique:inspector,phonenumber';
        }

        Validate::request($request->all(), [
            'name'          => 'required|string',
            'phonenumber'   => $phonenumber,
            'identerprise'  => 'required|string'
        ]);   
        $no_hp      = GlobalHelper::replace_hp($request->phonenumber);

        try {
            $inspector = Inspector::where('idinspector', $id)
                ->update([
                    'name'          => $request->name,                  
                    'phonenumber'   => $no_hp,         
                    'client_enterprise_identerprise'   => $request->identerprise,                      
                    'updated_by'    => auth()->guard('api')->user()->id
                ]);      

            $dataraw = '';
            $reason  = 'Update Inspector #';
            $trxid   = $id;
            $model   = 'inspector';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("inspector.failure_save_inspector");
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Inspector  $inspector
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $inspector = Inspector::where('idinspector',$id)->first();

        if ($inspector){
            $inspector =$inspector->delete();
            
            $dataraw = '';
            $reason  = 'Delete Inspector #';
            $trxid   = $id;
            $model   = 'inspector';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("inspector.failure_delete_inspector", ['id' => $id]);
        }
           

    }

    
}
