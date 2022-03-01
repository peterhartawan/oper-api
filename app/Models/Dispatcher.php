<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Dispatcher
 *
 * @property int $iddispatcher
 * @property string $name
 * @property int $vendor_idvendor
 *
 * @property \App\Models\Vendor $vendor
 * @property \Illuminate\Database\Eloquent\Collection $dispatcher_handle_companies
 * @property \Illuminate\Database\Eloquent\Collection $drivers
 * @property \Illuminate\Database\Eloquent\Collection $orders
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class Dispatcher extends Model
{
	use NullToEmptyString;

    protected $connection = 'mysql';
	protected $table = 'dispatcher';
	protected $primaryKey = 'iddispatcher';
	public $timestamps = false;

	protected $casts = [
	];

	protected $fillable = [
		'users_id',
		'nik',
		'birthdate',
		'gender',
		'address',
		'created_by',
        'updated_by',
        'status'
	];

	public function user()
	{
		return $this->belongsTo(\App\User::class, 'users_id');
	}
}
