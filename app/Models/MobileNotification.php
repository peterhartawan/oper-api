<?php

/**
 * Date: Thu, 20 april 2019.
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
class MobileNotification extends Model
{
	use NullToEmptyString;
	
	protected $guarded = [];
	protected $table = 'mobile_notification';
	protected $primaryKey = 'id';
	public $timestamps = true;

	protected $casts = [
		'user_id' => 'int',
	];

	protected $fillable = [
		'user_id',
		'device_id',
		'token',
		'device_type',
		'device_info'
	];

	public function user()
	{
		return $this->belongsTo(\App\User::class, 'user_id');
	}

}
