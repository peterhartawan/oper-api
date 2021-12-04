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
        $idorder     = $request->query('idorder');
             
        $trackingtask       = TrackingTask::select('tracking_task.*')
                            ->join('order','order.idorder','tracking_task.idorder')
                            ->where('tracking_task.status', Constant::STATUS_ACTIVE);   

        if(!empty($idorder)){        
            $trackingtask = $trackingtask->where("tracking_task.idorder",$idorder);
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
            'longitude'  => "required|string"
        ]);

        $userid = auth()->guard('api')->user()->id;
        $role = auth()->guard('api')->user()->idrole;

        $driver = Driver::where("users_id", $userid)
                ->leftJoin('users','driver.users_id','=','users.id')
                // ->where('driver.is_on_order',Constant::BOOLEAN_TRUE)
                ->join('order','order.driver_userid','=','users.id')
                ->where('order.order_status',Constant::ORDER_INPROGRESS)
                ->get();

        $employee = Employee::where("users_id", $userid)
            ->leftJoin('users', 'employee.users_id', '=', 'users.id')
            // ->where('employee.is_on_task', Constant::BOOLEAN_TRUE)

            ->join('order','order.employee_userid','=','users.id')
            ->where('order.order_status',Constant::ORDER_INPROGRESS)
            ->get();

        try {
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
                    $order = Order::where('driver_userid', $userid)
                        ->where('order_status', Constant::ORDER_INPROGRESS)
                        ->orderby('idorder', 'DESC')
                        ->get();
                } elseif ($role == Constant::ROLE_EMPLOYEE) {
                    $order = Order::where('employee_userid', $userid)
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

                    $trackingtask = TrackingTask::create([
                        'idorder'       => $detailorder->idorder,
                        'latitude'      => $request->latitude, 
                        'longitude'     => $request->longitude, 
                        'status'        => Constant::STATUS_ACTIVE,
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

   
}
