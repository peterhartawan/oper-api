<?php
namespace App\Exports;

use App\Models\Attendance;
use App\Models\Driver;
use App\Models\Vendor;
use Maatwebsite\Excel\Concerns\FromCollection;
use App\Constants\Constant;
use Maatwebsite\Excel\Concerns\WithHeadings;
use DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class AttendanceExport implements FromCollection,WithHeadings
{
    protected $userid;
    protected $month;
    protected $start_date;
    protected $end_date;

    public function __construct($userid=null, $month=null, $start_date=null, $end_date=null)
    {
        $this->userid       = $userid;
        $this->month        = $month;
        $this->start_date   = $start_date;
        $this->end_date     = $end_date;
    }

    public function headings(): array
    {
        return [
            'Name', 'Tier','Link Photo','Checkin date','Checkout date','Checkin Time','Description', 
            'Checkout Time', 'Total Hours'
        ];
    }
    public function collection()
    {
        $role_login = auth()->guard('api')->user()->idrole;
        if($role_login == Constant::ROLE_VENDOR) {
            $attendances = Attendance::select('users.name as name', 'employee_position.job_name', 'attendance.image_url',
            DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y ') as clock_in_date"),
            DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y') as clock_out_date"),
            DB::raw("DATE_FORMAT(attendance.clock_in, '%H:%i:%s') as clock_in"), 'attendance.remark',
            DB::raw("DATE_FORMAT(attendance.clock_out, '%H:%i:%s') as clock_out"),
            DB::raw("CAST(time_to_sec(timediff(attendance.clock_out,attendance.clock_in)) / 3600 AS DECIMAL(10,2)) as total_hour "))
            ->where('users.status', Constant::STATUS_ACTIVE)
            ->join('users', 'users.id', '=', 'attendance.users_id')
            ->join('employee', 'employee.users_id', '=', 'attendance.users_id')
            ->join('employee_position', 'employee.idemployee_position', '=', 'employee_position.idemployee_position')
            ->where('users.vendor_idvendor', auth()->guard('api')->user()->vendor_idvendor)
            ->orderBy("attendance.id", "desc");
        } elseif($role_login == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS || $role_login == Constant::ROLE_ENTERPRISE || $role_login == Constant::ROLE_SUPERADMIN) {
            $attendances = Attendance::select('users.name as name', 'employee_position.job_name', 'attendance.image_url',

            DB::raw("DATE_FORMAT(attendance.clock_in, '%a, %d %M %Y ') as clock_in_date"),
            DB::raw("DATE_FORMAT(attendance.clock_out, '%a, %d %M %Y') as clock_out_date"),
            DB::raw("DATE_FORMAT(attendance.clock_in, '%H:%i:%s') as clock_in"), 'attendance.remark',
            DB::raw("DATE_FORMAT(attendance.clock_out, '%H:%i:%s') as clock_out"),
            DB::raw("CAST(time_to_sec(timediff(attendance.clock_out,attendance.clock_in)) / 3600 AS DECIMAL(10,2)) as total_hour "))
            ->where('users.status', Constant::STATUS_ACTIVE)
            ->join('users', 'users.id', '=', 'attendance.users_id')
            ->join('employee', 'employee.users_id', '=', 'attendance.users_id')
            ->join('employee_position', 'employee.idemployee_position', '=', 'employee_position.idemployee_position')
            ->where('users.client_enterprise_identerprise', auth()->guard('api')->user()->client_enterprise_identerprise)
            ->orderBy("attendance.id", "desc");
        }        

        if (!empty($this->userid)) {
            $attendances = $attendances->where("attendance.users_id", $this->userid);
        }
        if (!empty($this->month)) {
            $attendances = $attendances->whereMonth('clock_in', '=', $this->month);
        } else {
            if (!empty($this->start_date)) {
                $attendances = $attendances->whereDate('clock_in', '>=', $this->start_date);
            }
            if (!empty($this->end_date)) {
                $attendances = $attendances->whereDate('clock_in', '<=', $this->end_date);
            }
        }
        
        $attendances = $attendances->get();
        $no = 1;
        array_walk($attendances, function (&$v, $k) use ($no) {
            foreach ($v as $item) {
                // $item->no = $no;
                if (!empty($item->image_url)) {
                    $item->image_url = env('BASE_API') . Storage::url($item->image_url);
                }
            }
            // $no++;
        });
        return $attendances;
    }
}
