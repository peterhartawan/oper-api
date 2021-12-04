<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Vendor
 * 
 * @property int $idvendor
 * @property string $name
 * @property string $desc
 * 
 * @property \Illuminate\Database\Eloquent\Collection $dispatchers
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class VehicleType extends Model
{
	use NullToEmptyString;
	
	protected $table = 'vehicle_type';
	protected $primaryKey = 'id';
	public $timestamps = false;

	protected $fillable = [
		'vehicle_brand_id',
		'type_name',
	];

	public function vehicle_brand()
	{
		return $this->belongsTo(\App\Models\VehicleBrand::class, 'vehicle_brand_id');
	}


}
