<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Http\Helpers\Paginator;
use App\Constants\Constant;
use DB;

class EventLogController extends Controller
{
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function index(Request $request)
    {
        $iduser = $request->userid;

        $logdata = DB::connection('mysql2');

        $logdata = $logdata->table('event_log')
                    ->select('event_log.*')
                    ->orderby('created_at','desc');

        if(!empty($iduser)){
            $logdata = $logdata->where("event_log.created_by","=",$iduser);
        }

        $logdata = $logdata->get();
        $page = $request->page ? $request->page : 1 ;
        $perPage = $request->query('limit')?? Constant::LIMIT_PAGINATION;
        $all_data= collect($logdata);
        $logdata2 = new Paginator($all_data->forPage($page, $perPage), $all_data->count(), $perPage, $page, [
            'path' => url("attendance/reporting?type=driver")
        ]); 

        return Response::success($logdata2);

    }
    
}