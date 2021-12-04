<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Driver
 * 
 * @property int $iddriver
 * @property string $email
 * @property string $password
 * @property string $fullname
 * @property string $address
 * @property string $phonenumber
 * @property int $drivertype_iddrivertype
 * @property int $dispatcher_iddispatcher
 * @property int $dispatcher_vendor_idvendor
 * 
 * @property \App\Models\Dispatcher $dispatcher
 * @property \App\Models\Drivertype $drivertype
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package App\Models
 */
class Employee extends Model
{
	use NullToEmptyString;

	protected $guarded = [];
	protected $table = 'employee';
	protected $primaryKey = 'idemployee';
	public $timestamps = false;

	protected $hidden = [
		'password'
	];

	protected $casts = [
		'is_on_task' => 'boolean'
	];

	protected $fillable = [
		'users_id',
		'nik',
		'idemployee_position',
		'birthdate',
		'gender',
		'address',
		'created_by',
		'updated_by',
        'status',
		'is_on_task',
		'attendance_latitude',
		'attendance_longitude'
	];


	public function user()
	{
		return $this->belongsTo(\App\User::class, 'users_id');
	}

	public function employee_position()
	{
		return $this->belongsTo(\App\Models\EmployeePosition::class, 'idemployee_position');
	}
	
}
