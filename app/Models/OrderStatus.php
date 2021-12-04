<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderStatus
 * 
 * @property int $idorder_status
 * @property string $name
 * 
 * @property \Illuminate\Database\Eloquent\Collection $orders
 *
 * @package App\Models
 */
class OrderStatus extends Model
{
	protected $table = 'order_status';
	protected $primaryKey = 'idorder_status';
	public $timestamps = false;

	protected $fillable = [
		'name'
	];

	public function orders()
	{
		return $this->hasMany(\App\Models\Order::class, 'order_status_idorder_status');
	}
}
