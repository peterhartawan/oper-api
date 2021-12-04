<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class EnterpriseType
 * 
 * @property int $identerprise_type
 * @property string $name
 * @property string $description
 * 
 * @property \Illuminate\Database\Eloquent\Collection $client_enterprises
 *
 * @package App\Models
 */

class EnterpriseType extends Model
{
	use NullToEmptyString;
	
	protected $table = 'enterprise_type';
	protected $primaryKey = 'identerprise_type';
	public $incrementing = false;
	public $timestamps = false;

	protected $casts = [
		'identerprise_type' => 'int'
	];

	protected $fillable = [
		'name',
		'description'
	];

	public function client_enterprises()
	{
		return $this->hasMany(\App\Models\ClientEnterprise::class, 'enterprise_type_identerprise_type');
	}
}
