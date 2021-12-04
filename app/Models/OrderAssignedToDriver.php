<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderAssignedToDriver
 * 
 * @property int $order_idorder
 * @property int $order_dispatcher_iddispatcher
 * @property int $driver_iddriver
 * 
 * @property \App\Models\Driver $driver
 * @property \App\Models\Order $order
 *
 * @package App\Models
 */
class OrderAssignedToDriver extends Model
{
	protected $table = 'order_assigned_to_driver';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'order_idorder' => 'int',
		'order_dispatcher_iddispatcher' => 'int',
		'driver_iddriver' => 'int'
	];

	public function driver()
	{
		return $this->belongsTo(\App\Models\Driver::class, 'driver_iddriver');
	}

	public function order()
	{
		return $this->belongsTo(\App\Models\Order::class, 'order_idorder');
	}
}
