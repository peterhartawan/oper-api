<?php

namespace App\Http\Controllers;

use App\Models\TrackingTask;
use App\Models\TrackingAttendance;
use Illuminate\Http\Request;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\constant;
use App\Services\Validate;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Employee;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Http\Helpers\EventLog;

class TrackingController extends Controller
{

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function listTrackingTask(Request $request)
    {
        $idorder                    = $request->query('idorder');
        $identerprise               = auth()->guard('api')->user()->client_enterprise_identerprise;

        $tracking_task_connection   = $this->switchTrackingTaskConnection($identerprise);

        $trackingtask               = $tracking_task_connection
                                        ->where('status', Constant::STATUS_ACTIVE);

        if(!empty($idorder)){
            $trackingtask = $trackingtask->where("idorder",$idorder);
        }

        return Response::success($trackingtask->paginate($request->query('limit') ?? 10));
    }

    public function listTrackingAttendance(Request $request)
    {
        $idattendance     = $request->query('idattendance');

        $trackingattendance = TrackingAttendance::select('tracking_attendance.*')
                            ->join('attendance','attendance.id','tracking_attendance.idattendance')
                            ->where('tracking_attendance.status', Constant::STATUS_ACTIVE);

        if(!empty($idattendance)){
            $trackingattendance = $trackingattendance->where("tracking_attendance.idattendance",$idattendance);
        }

        return Response::success($trackingattendance->paginate($request->query('limit') ?? 10));
    }

    public function store(Request $request)
    {
        Validate::request($request->all(), [
            'latitude'   => "required|string",
            'longitude'  => "required|string",
            'address'    => "nullable|string"
        ]);

        $userid = auth()->guard('api')->user()->id;
        $role = auth()->guard('api')->user()->idrole;
        $identerprise = auth()->guard('api')->user()->client_enterprise_identerprise;

        $driver_user_ids    = Driver::where('users_id', $userid)
                                ->pluck('users_id')->toArray();
        $driver             = $this->switchOrderConnection($identerprise)
                                ->with(['driver'])
                                ->wherein('driver_userid', $driver_user_ids)
                                ->where('order_status', Constant::ORDER_INPROGRESS)
                                ->get();
        $employee_user_ids  = Employee::where('users_id', $userid)
                                ->pluck('users_id')->toArray();
        $employee           = $this->switchOrderConnection($identerprise)
                                ->with(['employee'])
                                ->wherein('employee_userid', $employee_user_ids)
                                ->where('order_status', Constant::ORDER_INPROGRESS)
                                ->get();

        try {
            $order_connection = $this->switchOrderConnection($identerprise);

            if (count($driver) == 0 && count($employee) == 0) {
                $attendance = Attendance::where('users_id', $userid)
                    ->orderby('id','DESC')
                    ->first();

                if (empty($attendance)) {
                    return Response::success(['Tracking not saved. You did not checkin or in order.']);
                }

                $trackingattendance = TrackingAttendance::create([
                    'idattendance'  => $attendance->id,
                    'latitude'      => $request->latitude,
                    'longitude'     => $request->longitude,
                    'status'        => Constant::STATUS_ACTIVE,
                    'created_by'    => $userid,

                ]);
                $report                 = new \stdClass();
                $report->idattendance   = $trackingattendance->idattendance;
                $report->latitude       = $trackingattendance->latitude;
                $report->longitude      = $trackingattendance->longitude;
                $report->status         = $trackingattendance->status;
                $report->delay          = Constant::DELAY_ATTENDANCE;
                return Response::success($report);

            } else {
                if ($role == Constant::ROLE_DRIVER) {
                    $order = $order_connection->where('driver_userid', $userid)
                        ->where('order_status', Constant::ORDER_INPROGRESS)
                        ->orderby('idorder', 'DESC')
                        ->get();
                } elseif ($role == Constant::ROLE_EMPLOYEE) {
                    $order = $order_connection->where('employee_userid', $userid)
                        ->where('order_status', Constant::ORDER_INPROGRESS)
                        ->orderby('idorder', 'DESC')
                        ->get();
                } else {
                    throw new ApplicationException("errors.access_denied");
                }
                $idorder        = [];
                $latitude       = [];
                $longitude      = [];
                $status         = [];
                $delay          = [];

                foreach($order as $detailorder){

                    $trackingtask = $this->switchTrackingTaskConnection($identerprise)->create([
                        'idorder'       => $detailorder->idorder,
                        'latitude'      => $request->latitude,
                        'longitude'     => $request->longitude,
                        'status'        => Constant::STATUS_ACTIVE,
                        'address'       => $request->address,
                        'created_by'    => $userid,
                        'delay'         => Constant::DELAY_TASK
                    ]);

                    $idorder[]   = $trackingtask->idorder;
                    $latitude[]  = $trackingtask->latitude;
                    $longitude[] = $trackingtask->longitude;
                    $status[]    = $trackingtask->status;
                    $delay[]     = Constant::DELAY_TASK;

                }

                $report                 = new \stdClass();
                $report->idorder        = $idorder;
                $report->latitude       = $latitude;
                $report->longitude      = $longitude;
                $report->status         = $status;
                $report->delay          = Constant::DELAY_TASK;
                return Response::success($report);
            }

        }
        catch (Exception $e) {
            throw new ApplicationException("trackingtask.failure_save_trackingtask");
        }
    }

    public function listTrackingTaskWithDriver(Request $request)
    {
        $idorder                    = $request->query('idorder');
        $identerprise               = auth()->guard('api')->user()->client_enterprise_identerprise;

        $tracking_task_connection   = $this->switchTrackingTaskConnection($identerprise);

        $trackingtask               = $tracking_task_connection
                                        ->where('status', Constant::STATUS_ACTIVE)
                                        ->with(['order', 'order.driver'])
                                        ->orderBy('created_at', 'desc');

        if(!empty($idorder)){
            $trackingtask = $trackingtask->where("idorder",$idorder);
        }

        return Response::success($trackingtask->paginate($request->query('limit') ?? 10));
    }

    //get connection for cross-server queries
    private function switchOrderConnection($identerprise){
        $connection = Order::on('mysql');
        if($identerprise == env('CARS24_IDENTERPRISE')) {
            $connection = Order::on('cars24');
        }
        return $connection;
    }

    //get connection for cross-server queries
    private function switchTrackingTaskConnection($identerprise){
        $connection = TrackingTask::on('mysql');
        if($identerprise == env('CARS24_IDENTERPRISE')) {
            $connection = TrackingTask::on('cars24');
        }
        return $connection;
    }
}
