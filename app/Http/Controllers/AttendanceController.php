<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\Driver;
use App\Models\Employee;
use App\Models\Vendor;
use Validator;
use GuzzleHttp;
use App\Services\Validate;
use App\Services\Response;
use App\Constants\Constant;
use App\Exceptions\ApplicationException;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Exports\AttendancePriceExport;
use App\Exports\AttendanceExport;
use App\Exports\AttendanceDriverExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Helpers\Paginator;
use Illuminate\Http\Resources\Json\PaginatedResourceResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use App\Http\Helpers\EventLog;
use App\Models\B2C\CustomerB2C;
use App\Models\B2C\OrderB2C;
use App\Services\QontakHandler;
use Illuminate\Support\Facades\Log;

class AttendanceController extends Controller
{

    public function clock_in(Request $request)
    {
		Validate::request($request->all(), [
            'latitude'  => 'nullable|string',
            'longitude' => 'nullable|string',
            'remark'    => 'nullable|string',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg|max:'.Constant::MAX_IMAGE_SIZE
        ]);

        $idrole         = auth()->guard('api')->user()->idrole;
        $identerprise   = auth()->guard('api')->user()->client_enterprise_identerprise;

        if($idrole == Constant::ROLE_DRIVER){
            //validasi jarak untuk driver
            $driver         = Driver::where('users_id',auth()->guard('api')->user()->id)->first();
        }else{
            //validasi jarak untuk employee
            $driver         = Employee::where('users_id',auth()->guard('api')->user()->id)->first();
        }

        if (env('LOCK_LOCATION') == TRUE && $driver->attendance_latitude != null && $driver->attendance_longitude != null) {
            $driver_lat     = $driver->attendance_latitude;
            $driver_long    = $driver->attendance_longitude;
            $latitude       = $request->latitude;
            $longitude      = $request->longitude;

            try {
                $client         = new GuzzleHttp\Client(['http_errors' => false]);
                $url            = "https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins=".$driver_lat.",".$driver_long."&destinations=".$latitude.",".$longitude."&key=".env('GOOGLE_MAP_API_KEY', '');
                $request_url    = $client->get($url);

                Log::info($url);

                $response_file  = json_decode($request_url->getBody()->getContents(), true);
            } catch (Exception $e) {
                throw new ApplicationException("attendance.failure_clockin");
            }

            if($response_file['status'] == 'OK'){
                $jarak = 0;
                if($response_file['rows'][0]['elements'][0]['status'] && $response_file['rows'][0]['elements'][0]['status'] == 'OK'){
                    $jarak          = $response_file['rows'][0]['elements'][0]['distance']['value'];
                }

                if($jarak > env('RADIUS')){
                    throw new ApplicationException("attendance.failure_clock_in");
                }
            }else{
                throw new ApplicationException("attendance.failure_clock_in_setting_lock");
            }


        }

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
        }else{

            DB::beginTransaction();
            try {
                //Check B2C
                if($identerprise == env("B2C_IDENTERPRISE")){
                    //empty link
                    $request_link = $request->link;
                    if(empty($request_link)){
                        throw new ApplicationException("attendance.failure_b2c_empty_link");
                    }

                    //query for b2c order & link
                    $order_b2c = OrderB2C::where('link', $request_link)->first();
                    if(empty($order_b2c)){
                        throw new ApplicationException("attendance.failure_b2c_qr_not_found");
                    }

                    $link = $order_b2c->link;
                    //link mismatch
                    if($request_link != $link){
                        throw new ApplicationException("attendance.failure_b2c_qr_mismatch");
                    }

                    //link matched
                    //update order status
                    OrderB2C::where('link', $request_link)
                        ->update([
                            'status' => 2,
                            'time_start' => Carbon::now()->format('Y-m-d H:i'),
                        ]);

                    $customer_id = $order_b2c->customer_id;
                    $phone = CustomerB2C::where('id', $customer_id)->first()->phone;
                    $qontakHandler = new QontakHandler();
                    $qontakHandler->sendMessage(
                        "62".$phone,
                        "Order Began",
                        Constant::QONTAK_TEMPLATE_ORDER_BEGAN,
                        []
                    );
                }

                $attendance = new Attendance();

                $attendance->users_id = auth()->guard('api')->user()->id;
                $attendance->clock_in = Carbon::now();
                $attendance->remark =  $request->remark;
                $attendance->clock_in_latitude =  $request->latitude;
                $attendance->clock_in_longitude = $request->longitude;
                $attendance->created_by = $request->user()->id;

                if($request->hasfile('photo'))
                {
                    $path = Storage::putFile("/public/images/attendance", $request->file('photo'));
                    $attendance->image_url = $path;
                }
                $attendance->save();

                // change image url to laravel path
                if(!empty($attendance->image_url)){
                    $attendance->image_url = Storage::url($attendance->image_url);
                }

                $dataraw = '';
                $reason  = 'Clock in Attendance #';
                $trxid   = auth()->guard('api')->user()->id;
                $model   = 'driver';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                DB::commit();
                return Response::success($attendance);

            } catch (Exception $e) {
                DB::rollBack();
                throw new ApplicationException("attendance.failure_save_attendance");
            }
        }

    }

    public function clock_out(Request $request)
    {
		Validate::request($request->all(), [
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string'
        ]);

        $identerprise   = auth()->guard('api')->user()->client_enterprise_identerprise;

        $attendance =  Attendance::
                        where('users_id', auth()->guard('api')->user()->id)
                        ->whereNull("clock_out")
                        ->orderBy("clock_in","desc")
                        ->first();

        if($attendance){

            try {
                //Check B2C
                if($identerprise == env("B2C_IDENTERPRISE")){
                    //empty link
                    $request_link = $request->link;
                    if(empty($request_link)){
                        throw new ApplicationException("attendance.failure_b2c_empty_link");
                    }

                    //query for b2c order & link
                    $order_b2c = OrderB2C::where('link', $request_link)->first();
                    if(empty($order_b2c)){
                        throw new ApplicationException("attendance.failure_b2c_qr_not_found");
                    }

                    $link = $order_b2c->link;
                    //link mismatch
                    if($request_link != $link){
                        throw new ApplicationException("attendance.failure_b2c_qr_mismatch");
                    }

                    //link matched
                    //update order status
                    OrderB2C::where('link', $request_link)
                        ->update([
                            'status' => 3,
                            'time_end' => Carbon::now()->format('Y-m-d H:i'),
                        ]);

                    $customer_id = $order_b2c->customer_id;
                    $phone = CustomerB2C::where('id', $customer_id)->first()->phone;
                    $qontakHandler = new QontakHandler();
                    $qontakHandler->sendMessage(
                        "62".$phone,
                        "Order Began",
                        Constant::QONTAK_TEMPLATE_PAYMENT,
                        [
                            [
                                "key"=> "1",
                                "value"=> "rekening",
                                "value_text"=> "Rekening BCA PT. Online Helper Internasional : 889112381239"
                            ],
                            [
                                "key"=> "2",
                                "value"=> "link",
                                "value_text"=> "https://operdriverstaging.oper.co.id/invoice/" . $request_link
                            ],
                        ]
                    );
                }

                $attendance->update([
                    'users_id' => auth()->guard('api')->user()->id,
                    'clock_out' => Carbon::now(),
                    'clock_out_latitude' => $request->latitude,
                    'clock_out_longitude' => $request->longitude,
                    'updated_by'=> $request->user()->id
                 ]);

                 // change image url to laravel path
                 if(!empty($attendance->image_url)){
                     $attendance->image_url = Storage::url($attendance->image_url);
                 }

                $dataraw = '';
                $reason  = 'Clock out Attendance #';
                $trxid   = auth()->guard('api')->user()->id;
                $model   = 'driver';
                EventLog::insertLog($trxid, $reason, $dataraw,$model);

                 return Response::success($attendance);

            } catch (Exception $e) {
                throw new ApplicationException("attendance.failure_save_attendance");
            }

        }else{

            // check if today already clock in
            $checkAttendance = Attendance::
                                where('users_id', auth()->guard('api')->user()->id)
                                ->where("clock_in",">=",Carbon::today()->toDateString());

            if($checkAttendance->count() < 1){
                // if clock in not found, user have to clock in first
                throw new ApplicationException("attendance.failure_not_yet_clock_in");
            }else{
                // if already clock in then user already clockout
                throw new ApplicationException("attendance.failure_already_clockout");
            }
        }

    }

    public function last_status()
    {
        $attendance = Attendance::where("users_id",auth()->guard('api')->user()->id)
                        ->whereDate("clock_in",Carbon::today()->toDateString())
                        ->first();

        $nextAction = "";
        if(empty($attendance->clock_in)){
            $attendance2 = Attendance::where("users_id",auth()->guard('api')->user()->id)
                ->whereDate("clock_in","!=",Carbon::today()->toDateString())
                ->orderBy("clock_in","desc")
                ->first();
            if(!empty($attendance2->clock_in) && empty($attendance2->clock_out)){
                $nextAction = "clock_out";
                return Response::success(["next_action"=>$nextAction,"last_attendance"=>$attendance2]);
            }else{
                $nextAction = "clock_in";
            }
        }else if (!empty($attendance->clock_in) && empty($attendance->clock_out)){
            $nextAction = "clock_out";
        }else{
            $nextAction = "completed";
        }

        return Response::success(["next_action"=>$nextAction,"last_attendance"=>$attendance]);

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
    public function reporting(Request $request)
    {
        $role_login = auth()->guard('api')->user()->idrole ;
        $type = $request->query('type');

        //Mobile
        if ($role_login == Constant::ROLE_DRIVER) {
            return $this->reportingDriver($request);
        } elseif ($role_login == Constant::ROLE_EMPLOYEE) {
            return $this->reportingEmployee($request);
        }

        //Web Vendor
        if ($role_login == Constant::ROLE_VENDOR || $role_login == Constant::ROLE_VENDOR_SUB || $role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER || $role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS) {
            if ($type == 'driver') {
                return $this->reportingDriver($request);
            } else {
                return $this->reportingEmployee($request);
            }
        }
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
    private function reportingDriver(Request $request){
        $role_login  = auth()->guard('api')->user()->idrole ;
        $export      = $request->query('export');

        if($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER ) {
            throw new ApplicationException("errors.access_denied");
        }
        if($role_login == Constant::ROLE_DRIVER){
            $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"))
                        ->where('users.status', Constant::STATUS_ACTIVE)
                        ->join('users', 'users.id', '=', 'attendance.users_id')
                        ->join('driver', 'driver.users_id', '=', 'attendance.users_id')
                        ->where('driver.users_id',auth()->guard('api')->user()->id)
                        ->whereDate('clock_in', '>=', Carbon::now()->subDays(14))
                        ->whereDate('clock_in', '<=', Carbon::now())
                        ->orderBy("attendance.id","desc");

        }else{
            if($role_login == Constant::ROLE_VENDOR || $role_login == Constant::ROLE_VENDOR_SUB) {
                $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"), 'client_enterprise.name as nama_enterprise')
                ->where('users.status', Constant::STATUS_ACTIVE)
                ->join('users', 'users.id', '=', 'attendance.users_id')
                ->join('driver', 'driver.users_id', '=', 'attendance.users_id')
                ->leftJoin('client_enterprise', 'client_enterprise.identerprise', '=', 'users.client_enterprise_identerprise')
                ->where('users.vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
                ->orderBy("attendance.id","desc");

            } elseif($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS || $role_login == Constant::ROLE_ENTERPRISE || $role_login == Constant::ROLE_SUPERADMIN) {
                $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"),  'client_enterprise.name as nama_enterprise')
                ->where('users.status', Constant::STATUS_ACTIVE)
                ->join('users', 'users.id', '=', 'attendance.users_id')
                ->join('driver', 'driver.users_id', '=', 'attendance.users_id')
                ->leftJoin('client_enterprise', 'client_enterprise.identerprise', '=', 'users.client_enterprise_identerprise')
                ->where('users.client_enterprise_identerprise', auth()->guard('api')->user()->client_enterprise_identerprise)
                ->orderBy("attendance.id","desc");
            }

            if($request->query('userid')){
                $attendances = $attendances->where("attendance.users_id",$request->query('userid'));
            }

            if($request->query('month')){
                $attendances = $attendances->whereMonth('clock_in', '=', $request->query('month'));
            }else{
                if($request->query('start_date') ){
                    $attendances = $attendances->whereDate('clock_in', '>=', $request->query('start_date'));
                }

                if($request->query('end_date') ){
                    $attendances = $attendances->whereDate('clock_in', '<=', $request->query('end_date'));
                }
            }

        }
        $attendances = $attendances->get();
        if ($export == Constant::BOOLEAN_TRUE && $request->query('type') == 'driver') {
            $date       = Carbon::now();
            $userid     = $request->query('userid');
            $month      = $request->query('month');
            $start_date = $request->query('start_date');
            $end_date   = $request->query('end_date');
            // $vendor     = Vendor::where('idvendor', $user->vendor_idvendor)->first();

            if (!empty($start_date)) {
                    if($attendances->isEmpty()) {
                        throw new ApplicationException("orders.failure_attendance_driver");
                    } else {
                        $file_name = "Attendance_Driver_report_".$request->query('start_date').".xlsx";
                        Excel::store(new AttendanceDriverExport($userid, $month, $start_date, $end_date), '/public/file/' . $file_name);
                        $fileexport = Storage::url('file/' . $file_name);
                        return Response::success(["file export" => url($fileexport) ], 'messages.success', [], [], JSON_UNESCAPED_SLASHES);
                    }

            } else {
                throw new ApplicationException("orders.failure_date_select");
            }
        }


        $no = 1;
        array_walk($attendances, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->image_url)) {
                    $item->image_url = env('BASE_API') . Storage::url($item->image_url);
                }
            }
            $no++;
        });

        $no = 1;
        array_walk($attendances, function (&$v, $k) use ($no) {
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
        $all_attendances = collect($attendances);
        $attendances_new = new Paginator($all_attendances->forPage($page, $perPage), $all_attendances->count(), $perPage, $page, [
            'path' => url("attendance/reporting?type=driver")
        ]);
        return Response::success($attendances_new);

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
    private function reportingEmployee(Request $request){
        $user       = auth()->guard('api')->user();
        $export     = $request->query('export');
        $role_login = auth()->guard('api')->user()->idrole ;

        if($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER ) {
            throw new ApplicationException("errors.access_denied");
        }
        if($role_login == Constant::ROLE_EMPLOYEE){
            $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"))
                        ->where('users.status', Constant::STATUS_ACTIVE)
                        ->join('users', 'users.id', '=', 'attendance.users_id')
                        ->join('employee', 'employee.users_id', '=', 'attendance.users_id')
                        ->where('employee.users_id',auth()->guard('api')->user()->id)
                        ->whereDate('clock_in', '>=', Carbon::now()->subDays(14))
                        ->whereDate('clock_in', '<=', Carbon::now())
                        ->orderBy("attendance.id","desc");

        }else{

            if($role_login == Constant::ROLE_VENDOR ) {
                $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"))
                ->where('users.status', Constant::STATUS_ACTIVE)
                ->join('users', 'users.id', '=', 'attendance.users_id')
                ->join('employee', 'employee.users_id', '=', 'attendance.users_id')
                ->where('users.vendor_idvendor',auth()->guard('api')->user()->vendor_idvendor)
                ->orderBy("attendance.id","desc");

            } elseif($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS || $role_login == Constant::ROLE_ENTERPRISE || $role_login == Constant::ROLE_SUPERADMIN) {
                $attendances = Attendance::select('attendance.id','attendance.clock_in_latitude','attendance.clock_in_longitude','attendance.clock_out_latitude','attendance.clock_out_longitude','attendance.image_url','users.profile_picture','attendance.remark','users.name as name',DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y %H:%i:%s') as clock_out"),DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y %H:%i:%s') as clock_in"))
                ->where('users.status', Constant::STATUS_ACTIVE)
                ->join('users', 'users.id', '=', 'attendance.users_id')
                ->join('employee', 'employee.users_id', '=', 'attendance.users_id')
                ->where('users.client_enterprise_identerprise',auth()->guard('api')->user()->client_enterprise_identerprise)
                ->orderBy("attendance.id","desc");
            }

            if($request->query('userid')){
                $attendances = $attendances->where("attendance.users_id", $request->query('userid'));
            }
            if($request->query('month')){
                $attendances = $attendances->whereMonth('clock_in', '=', $request->query('month'));
            }else{
                if($request->query('start_date')){
                    $attendances = $attendances->whereDate('clock_in', '>=', $request->query('start_date'));
                }
                if($request->query('end_date')){
                    $attendances = $attendances->whereDate('clock_in', '<=', $request->query('end_date'));
                }
            }
        }

        $attendances = $attendances->get();
        if ($export == Constant::BOOLEAN_TRUE && $request->query('type') == 'employee') {
            $date = Carbon::now();
            $userid     = $request->query('userid');
            $month      = $request->query('month');
            $start_date = $request->query('start_date');
            $end_date   = $request->query('end_date');
            $vendor = Vendor::where('idvendor', $user->vendor_idvendor)->first();

            if (!empty($start_date)) {
                if ($vendor->show_employee_price == 1) {
                    $file_name = "AttendancePrice_export"."_".$request->query('start_date').".xlsx";
                    Excel::store(new AttendancePriceExport($userid, $month, $start_date, $end_date), '/public/file/' . $file_name);
                    if($attendances->isEmpty()) {
                        throw new ApplicationException("orders.failure_attendance_driver");
                    } else {
                        $fileexport = Storage::url('file/' . $file_name);
                        return Response::success(["file export" => url($fileexport) ], 'messages.success', [], [], JSON_UNESCAPED_SLASHES);
                    }
                } else {
                    $file_name = "Attendance_report_".$request->query('start_date').".xlsx";
                    Excel::store(new AttendanceExport($userid, $month, $start_date, $end_date), '/public/file/' . $file_name);
                    if($attendances->isEmpty()) {
                        throw new ApplicationException("orders.failure_attendance_driver");
                    } else {
                        $fileexport = Storage::url('file/' . $file_name);
                        return Response::success(["file export" => url($fileexport) ], 'messages.success', [], [], JSON_UNESCAPED_SLASHES);
                    }
                }
            } else {
                throw new ApplicationException("orders.failure_date_select");
            }
        }

        $no = 1;
        array_walk($attendances, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                $item->no = $no;
                if (!empty($item->image_url)) {
                    $item->image_url = env('BASE_API') . Storage::url($item->image_url);
                }
            }
            $no++;
        });

        $no = 1;
        array_walk($attendances, function (&$v, $k) use ($no) {
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
        $all_attendances = collect($attendances);
        $attendances_new = new Paginator($all_attendances->forPage($page, $perPage), $all_attendances->count(), $perPage, $page);
        $attendances_new = $attendances_new->setPath(url()->full());
        return Response::success($attendances_new);

    }


    /**
     * show attendance by id
     *
     * @param  [int] id
     * @return [json] driver object
     */
    public function show($id)
    {
        $attendance = Attendance::select('users.name as name', 'users.phonenumber as phonenumber', 'users.email as email','driver.*','attendance.*','employee.*')
                        ->leftjoin('users', 'users.id', '=', 'attendance.users_id')
                        ->leftjoin('driver', 'driver.users_id', '=', 'attendance.users_id')
                        ->leftjoin('employee', 'employee.idemployee', '=', 'attendance.users_id')
                        ->where('attendance.id', $id)
                        ->where('users.status','!=', Constant::STATUS_SUSPENDED)
                        ->first();

        // change image url to laravel path
        if(!empty($attendance->image_url)){
            $attendance->image_url = Storage::url($attendance->image_url);

            $attendance->image_url = env('BASE_API').$attendance->image_url;
        }

        if(empty($attendance)){
            throw new ApplicationException("errors.entity_not_found", ['entity' => 'Attendance','id' => $id]);
        }

        return Response::success($attendance);
    }

    /**
     * Remove attendance
     * This is just for test
     *
     * @param  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role_login = auth()->guard('api')->user()->idrole ;

        if ($role_login != Constant::ROLE_VENDOR)
            throw new ApplicationException("errors.access_denied");

        $deleted = Attendance::where('id', $id)->delete();
        if (!$deleted) {
            Response::error("Could not delete attendance.");
        }

        $dataraw = '';
        $reason  = 'Delete Attendance #';
        $trxid   = $id;
        $model   = 'driver';
        EventLog::insertLog($trxid, $reason, $dataraw,$model);

        return Response::success(['id' => $id]);
    }

    public function exportExcel(Request $request)
    {
        $role_login = auth()->guard('api')->user()->idrole ;
        $type       = $request->query('type');
        // $user       = $request->query('userid');
        // $month      = $request->query('month');
        // $start_date = $request->query('start_date');
        // $end_date   = $request->query('end_date');

        if ($role_login == Constant::ROLE_VENDOR) {
            if ($type == 'driver') {
                $createexcel =  Excel::store(new DriverExport($user, $month, $start_date, $end_date), 'public/file');
                return;
            } else {
                $createexcel =  Excel::store(new EmployeeExport($user, $month, $start_date, $end_date), 'public/file');
                return;
            }
        }
    }

}
