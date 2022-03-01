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
class Driver extends Model
{
	use NullToEmptyString;

    protected $connection = 'mysql';
	protected $guarded = [];
	protected $table = 'driver';
	protected $primaryKey = 'iddriver';
	public $timestamps = false;

	protected $casts = [
		'drivertype_iddrivertype' => 'int',
		'dispatcher_vendor_idvendor' => 'int',
		'is_on_order' => 'boolean'
	];

	protected $hidden = [
		'password'
	];

	protected $fillable = [
		'users_id',
		'birthdate',
		'address',
		'drivertype_iddrivertype',
		'created_by',
		'updated_by',
		'nik',
		'gender',
        'status',
		'insurance_policy_number',
		'attendance_latitude',
		'attendance_longitude'
	];

	public function drivertype()
	{
		return $this->belongsTo(\App\Models\Drivertype::class, 'drivertype_iddrivertype');
	}

	public function user()
	{
		return $this->belongsTo(\App\User::class, 'users_id');
	}

	public function orders()
	{
		return $this->belongsToMany(\App\Models\Order::class, 'order_assigned_to_driver', 'driver_iddriver', 'order_idorder')
					->withPivot('order_dispatcher_iddispatcher');
	}

	public function enterprise()
	{
		return $this->hasOne('App\Models\ClientEnterprise', 'identerprise', 'client_enterprise_identerprise')
		->with("enterprise_type");
	}
}
