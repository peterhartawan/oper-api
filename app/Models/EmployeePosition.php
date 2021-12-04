<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
class EmployeePosition extends Model
{
	protected $guarded = [];
	protected $table = 'employee_position';
	protected $primaryKey = 'idemployee_position';
	public $timestamps = true;

	protected $casts = [
		'idemployee_position' => 'int'
	];

	protected $fillable = [
		'job_name',
		'status',
		'price',
		'vendor_idvendor',
		'updated_by',
		'created_by'
	];
	
}