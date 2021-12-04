<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;
use Illuminate\Database\Eloquent\SoftDeletes;

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
class VehicleBrand extends Model
{
	use NullToEmptyString;
	use SoftDeletes;
	
	protected $table = 'vehicle_brand';
	protected $primaryKey = 'id';
	public $timestamps = false;

	protected $fillable = [
		'brand_name',
	];


}
