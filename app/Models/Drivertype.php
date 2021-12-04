<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Drivertype
 * 
 * @property int $iddrivertype
 * @property string $name
 * 
 * @property \Illuminate\Database\Eloquent\Collection $drivers
 *
 * @package App\Models
 */
class Drivertype extends Model
{
	use NullToEmptyString;
	
	protected $table = 'drivertype';
	protected $primaryKey = 'iddrivertype';
	public $timestamps = false;

	protected $fillable = [
		'name'
	];

	public function drivers()
	{
		return $this->hasMany(\App\Models\Driver::class, 'drivertype_iddrivertype');
	}
}
