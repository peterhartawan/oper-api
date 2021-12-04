<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\StaticContent;
use App\Models\WebMenu;
use App\Models\RoleAccess;
use App\Services\Response;
use App\Services\Validate;
use DB;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use App\Http\Helpers\EventLog;

class StaticContentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $idrole        = $request->query('idrole');

        try {
            if ($idrole) {
                $static = StaticContent::where('status', '=', Constant::STATUS_ACTIVE)
                ->where('idrole', $idrole)
                ->paginate($request->query('limit')  ?? Constant::LIMIT_PAGINATION);
            } else {
                $static = StaticContent::
                where('status', '=', Constant::STATUS_ACTIVE)
                ->paginate($request->query('limit')  ?? Constant::LIMIT_PAGINATION);
            }
            return Response::success($static);
        } catch (Exception $e) {
            throw new ApplicationException("staticcontent.failure_save_static_content");
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
     * Create Client Enterprise
     *
     * @param  [string] name
     * @param  [string] description
     * @param [int] idrole
     * @param [string] content
    */
    public function store(Request $request)
    {
        
        Validate::request($request->all(), [
            'name'          => 'required|string',
            'description'   => 'nullable',
            'content'       => 'string',
            'idrole'        => 'required|integer|exists:role',
        ]);

        DB::beginTransaction();
        try {
            $static = StaticContent::create([
                'name'          => $request->name,                
                'description'   => $request->description,
                'content'       => $request->content,
                'idrole'        => $request->idrole,
                'created_by'    => $request->user()->id               
            ]); 

            $dataraw = '';
            $reason  = 'Create static content #';
            $trxid   = $static->idstatic_content;
            $model   = 'static content';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            DB::commit();
            return Response::success($static);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("staticcontent.failure_save_static_content");
        }
        
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StaticContent  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $static = StaticContent::select('static_content.*')
                    ->where('idstatic_content', $id)
                    ->first();

            if (empty($static)) {
                throw new ApplicationException("staticcontent.failure_notfound_static_content");
            }

            return Response::success($static);
        } catch (Exception $e) {
            throw new ApplicationException("staticcontent.failure_notfound_static_content");
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StaticContent  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function edit(StaticContent $staticContent)
    {
        //
    }

    /**
     * Create Client Enterprise
     *
     * @param  [string] name
     * @param  [string] description
     * @param [int] idrole
     * @param [string] content
    */
    public function update(Request $request, $id)
    {
        Validate::request($request->all(), [
            'name'          => 'required|string',
            'content'       => 'string',
        ]);

      
        try {
            $static = StaticContent::where('idstatic_content', $id)
                ->update([
                    'name'          => $request->name,                  
                    'description'   => $request->description ?? "",  
                    'content'       => $request->content,
                    'updated_by'    => $request->user()->id 
                ]);     

            $dataraw = '';
            $reason  = 'Update static content #';
            $trxid   = $id;
            $model   = 'static content';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);

            return Response::success(['id' => $id]);
        } catch (Exception $e) {
            throw new ApplicationException("staticcontent.failure_save_static_content");
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
        // Privacy and TOC caanot be deleted
        $enterprise = StaticContent::where('idstatic_content', $id)
                        ->where('static_content.status',"=",Constant::STATUS_ACTIVE)
                        ->whereNotIn('idstatic_content', [1,2])
                        ->update([
                            'static_content.status' => Constant::STATUS_DELETED
                        ]);

        if ($enterprise > 0){
            $dataraw = '';
            $reason  = 'delete static content #';
            $trxid   = $id;
            $model   = 'static content';
            EventLog::insertLog($trxid, $reason, $dataraw,$model);
            return Response::success(['id' => $id]);
        }else{
            throw new ApplicationException("staticcontent.failure_delete_static_content", ['id' => $id]);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\StaticContent  $staticContent
     * @return \Illuminate\Http\Response
     */
    public function slug($slug)
    {
        $role_login = auth()->guard('api')->user()->idrole ;
        $slug       = "/pages/{$slug}";
        
        try {
            if (in_array($role_login, [Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS, Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER])) {
                $role_login = Constant::ROLE_VENDOR;
            }

            $static = StaticContent::select('static_content.*', 'web_menu.*')
                ->leftJoin('web_menu', 'web_menu.static_content_idstatic_content', '=', 'static_content.idstatic_content')
                ->where('web_menu.slug', $slug)
                ->where('idrole', $role_login)
                ->first();

            if (empty($static)) {
                throw new ApplicationException("staticcontent.failure_notfound_static_content");
            }
            
            return Response::success($static);
        } catch (Exception $e) {
            throw new ApplicationException("staticcontent.failure_notfound_static_content");
        }
    }
}
