<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Order
 *
 * @property int $idorder
 * @property int $order_status_idorder_status
 * @property int $dispatcher_iddispatcher
 * @property int $task_template_task_template_id
 *
 * @property \App\Models\Dispatcher $dispatcher
 * @property \App\Models\OrderStatus $order_status
 * @property \App\Models\TaskTemplate $task_template
 * @property \Illuminate\Database\Eloquent\Collection $drivers
 *
 * @package App\Models
 */
class Order extends Model
{
	use NullToEmptyString;
	// protected $dates = ['booking_time'];
	protected $table = 'order';
	protected $primaryKey = 'idorder';
	public $timestamps = true;

	protected $casts = [
		// 'booking_time' => 'date:l, d M Y',
		'order_type_idorder_type' => 'int',
		'client_userid' => 'int',
		'driver_userid' => 'int',
		'dispatcher_userid' => 'int',
		'employee_userid' => 'int',
		'task_template_task_template_id' => 'int'
	];

	protected $fillable = [
		'idorder',
		'trx_id',
		'task_template_task_template_id',
		'client_enterprise_identerprise',
		'client_userid',
		'driver_userid',
		'dispatcher_userid',
		'status',
		'booking_time',
		'origin_latitude',
		'origin_longitude',
		'user_fullname',
		'user_phonenumber',
		'vehicle_owner',
		'destination_latitude',
		'destination_longitude',
		'client_vehicle_license',
		'vehicle_brand_id',
		'vehicle_type',
		'vehicle_transmission',
		'vehicle_year',
		'vehicle_color',
		'message',
		'order_type_idorder_type',
		'order_status',
		'created_by',
		'updated_by',
		'employee_userid',
		'origin_name',
		'destination_name',
		'dispatch_at'
	];

	public function enterprise()
	{
		return $this->belongsTo(\App\Models\ClientEnterprise::class, 'client_enterprise_identerprise', 'identerprise')->with("enterprise_type");
	}

	public function driver()
	{
		return $this->belongsTo(\App\Models\Driver::class, 'driver_userid', 'users_id')->with(["user", "drivertype"]);
	}

	public function dispatcher()
	{
		return $this->belongsTo(\App\User::class, 'dispatcher_userid');
	}

	public function order_type()
	{
		return $this->belongsTo(\App\Models\OrderType::class, 'order_type_idorder_type');
	}

	public function order_tasks()
	{
		return $this->hasMany(\App\Models\OrderTasks::class);
	}

	public function task_template()
	{
		return $this->belongsTo(\App\Models\TaskTemplate::class, 'task_template_task_template_id');
	}

	public function vehicle_branch()
	{
	  return $this->belongsTo(\App\Models\VehicleBrand::class, 'vehicle_brand_id');
	}

	public function employee()
	{
		return $this->belongsTo(\App\Models\Employee::class, 'employee_userid', 'users_id')->with(["user", "employee_position"]);
	}

    public function tracking_task()
    {
        return $this->belongsTo(\App\Models\TrackingTask::class, 'idorder', 'idorder');
    }
}
