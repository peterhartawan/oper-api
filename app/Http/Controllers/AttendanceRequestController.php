<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Services\Validate;
use App\Services\Response;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use Exception;
use App\Http\Helpers\EventLog;
use App\Http\Helpers\Paginator;
use DB;
use Illuminate\Console\Application;
use Illuminate\Support\Facades\Storage;

class AttendanceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //Validate some fields
        Validate::request($request->all(), [
            'latitude'      => 'required|string',
            'longitude'     => 'required|string',
            'remark'        => 'required|string'
        ]);

        //Check if driver already requested for attendance today
        $checkRequest = AttendanceRequest
            ::where('created_by', auth()->guard('api')->user()->id)
            ->where("datetime",">=",Carbon::today()->toDateString())
            ->whereNull("approved_by")
            ->first();

        if($checkRequest->count() > 0){
            throw new ApplicationException("attendance-request.failure_already_requested");
        } else {
            DB::beginTransaction();
            try{
                $checkAttendance = Attendance::
                        where('users_id', auth()->guard('api')->user()->id)
                        ->where("clock_in",">=",Carbon::today()->toDateString());

                $attendance =  Attendance::
                            where('users_id', auth()->guard('api')->user()->id)
                            ->whereDate("clock_in","!=",Carbon::today()->toDateString())
                            ->whereNull("clock_out")
                            ->orderBy("clock_in","desc")
                            ->first();

                if($attendance){
                    throw new ApplicationException("attendance.failure_clockin");
                }

                if($checkAttendance->count() > 0){
                    throw new ApplicationException("attendance.failure_already_clockin");
                }

                $attendanceRequest = new AttendanceRequest();

                $attendanceRequest->created_by  = auth()->guard('api')->user()->id;
                $attendanceRequest->datetime    = Carbon::now();
                $attendanceRequest->remark      = $request->remark;
                $attendanceRequest->latitude    = $request->latitude;
                $attendanceRequest->longitude   = $request->longitude;

                $attendanceRequest->save();

                $dataraw = '';
                $reason  = 'Request Attendance #';
                $trxid   = auth()->guard('api')->user()->id;
                $model   = 'driver';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                DB::commit();
                return Response::success($attendanceRequest);

            } catch (Exception $e) {

                DB::rollBack();
                throw new ApplicationException("attendance-request.failure_save_request");

            }
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\AttendanceRequest  $attendanceRequest
     * @return \Illuminate\Http\Response
     */
    public function show(AttendanceRequest $attendanceRequest)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\AttendanceRequest  $attendanceRequest
     * @return \Illuminate\Http\Response
     */
    public function edit(AttendanceRequest $attendanceRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\AttendanceRequest  $attendanceRequest
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, AttendanceRequest $attendanceRequest)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\AttendanceRequest  $attendanceRequest
     * @return \Illuminate\Http\Response
     */
    public function destroy(AttendanceRequest $attendanceRequest)
    {
        //
    }

    /**
     * attendance report
     *
     * @param  [int] userid
     * @param  [int] month
     * @param  [Y-m-d] start_date
     * @param  [Y-m-d] end_date
     * @return [json] attendance object
     */
    public function reporting(Request $request){
        $role_login  = auth()->guard('api')->user()->idrole ;

        if($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS) {
            $requests = AttendanceRequest
            ::select('attendance_request.id', 'users.id as iduser', 'users.profile_picture', 'users.name', 'attendance_request.created_at', 'attendance_request.remark', 'attendance_request.latitude', 'attendance_request.longitude')
            ->where('users.status', Constant::STATUS_ACTIVE)
            ->join('users', 'users.id', '=', 'attendance_request.created_by')
            ->join('driver', 'driver.users_id', '=', 'attendance_request.created_by')
            ->join('client_enterprise', 'client_enterprise.identerprise', '=', 'users.client_enterprise_identerprise')
            ->where('users.client_enterprise_identerprise', auth()->guard('api')->user()->client_enterprise_identerprise)
            ->whereNull('attendance_request.approved_by')
            ->orderBy("attendance_request.id","desc");
        }

        $requests = $requests->get();

        $no = 1;
        array_walk($requests, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->image_url)) {
                    $item->image_url = env('BASE_API') . Storage::url($item->image_url);
                }
            }
            $no++;
        });

        $no = 1;
        array_walk($requests, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->profile_picture)) {
                    $item->profile_picture = env('BASE_API') . Storage::url($item->profile_picture);
                }
            }
            $no++;
        });

        $page = $request->page ? $request->page : 1 ;
        $perPage = $request->query('limit')?? Constant::LIMIT_PAGINATION;
        $all_requests = collect($requests);
        $requests_new = new Paginator($all_requests->forPage($page, $perPage), $all_requests->count(), $perPage, $page, [
            'path' => url("attendance/reporting?type=driver")
        ]);
        return Response::success($requests_new);

    }

    public function approve(Request $request){
        //Validate form data
        Validate::request($request->all(), [
            'attendanceRequests'                => 'array|required',
            'attendanceRequests.*.idreq'        => 'integer|required',
            'attendanceRequests.*.iduser'       => 'integer|required',
            'attendanceRequests.*.latitude'     => 'string|required',
            'attendanceRequests.*.longitude'    => 'string|required',
            'attendanceRequests.*.remark'       => 'string|required',
            'attendanceRequests.*.clock_in'     => 'string|required',
        ]);

        // dd($request->attendanceRequests[0]["idreq"]);

        foreach($request->attendanceRequests as $index => $attendanceRequest){
            //Check Atendance
            $attendance =  Attendance::where('users_id', $attendanceRequest["iduser"])
                ->whereDate("clock_in","!=",Carbon::today()->toDateString())
                ->whereNull("clock_out")
                ->orderBy("clock_in","desc")
                ->first();

            $checkAttendance = Attendance::
                        where('users_id', $attendanceRequest["iduser"])
                        ->where("clock_in",">=",Carbon::today()->toDateString());

            // dd($checkAttendance->count());
            if($attendance){
                throw new ApplicationException("attendance.failure_clockin");
            }

            if($checkAttendance->count() > 0){
                throw new ApplicationException("attendance.failure_already_clockin");
            }else{

                DB::beginTransaction();
                try {
                    $attendance_request = AttendanceRequest::where('id', $attendanceRequest["idreq"])
                        ->whereNull("approved_by")
                        ->orderBy("datetime", "desc")
                        ->first();
                    // dd($attendance_request->toArray());
                    $attendance_request->update([
                        "approved_by" => auth()->guard('api')->user()->id
                    ]);

                    $attendance = new Attendance();

                    $attendance->users_id = $attendanceRequest["iduser"];
                    $attendance->clock_in = $attendanceRequest["clock_in"];
                    $attendance->remark =  $attendanceRequest["remark"];
                    $attendance->clock_in_latitude =  $attendanceRequest["latitude"];
                    $attendance->clock_in_longitude = $attendanceRequest["longitude"];
                    $attendance->created_by = auth()->guard('api')->user()->id;

                    // dd($attendance->toArray());
                    $attendance->save();

                    $dataraw = '';
                    $reason  = 'Clock in Attendance #';
                    $trxid   = auth()->guard('api')->user()->id;
                    $model   = 'driver';
                    EventLog::insertLog($trxid, $reason, $dataraw,$model);

                    DB::commit();
                } catch (Exception $e) {
                    DB::rollBack();
                    throw new ApplicationException("attendance.failure_save_attendance");
                }
            }
        }

        return Response::success($attendance);
    }

    public function cancel(Request $request){
        //Validate form data
        Validate::request($request->all(), [
            'idreqs' => 'array|required',
        ]);

        DB::beginTransaction();
        try{
            AttendanceRequest::destroy($request->idreqs);
            DB::commit();
            return Response::success(['idreqs' => $request->idreqs]);
        } catch (Exception $e) {
            DB::rollBack();
            throw new ApplicationException("attendance-request.failure_cancel");
        }
    }
}
