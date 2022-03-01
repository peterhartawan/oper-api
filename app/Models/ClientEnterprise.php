<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;
use App\Constants\Constant;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class ClientEnterprise
 *
 * @property int $identerprise
 * @property string $name
 * @property string $description
 * @property int $enterprise_type_identerprise_type
 *
 * @property \App\Models\EnterpriseType $enterprise_type
 * @property \Illuminate\Database\Eloquent\Collection $dispatcher_handle_companies
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */

class ClientEnterprise extends Model
{
	use NullToEmptyString;

    protected $connection = 'mysql';
	protected $table = 'client_enterprise';
	protected $primaryKey = 'identerprise';
	public $timestamps = true;

	protected $casts = [
		'enterprise_type_identerprise_type' => 'int',
		'vendor_idvendor' => 'int'
	];

	protected $fillable = [
		'name',
		'email',
		'description',
		'enterprise_type_identerprise_type',
		'vendor_idvendor',
		'office_phone',
		'office_address',
		'pic_name',
		'pic_phone',
		'pic_email',
		'is_private',
		'site_url',
		'image_logo',
		'status',
		'created_by',
		'updated_by'
	];

	public function enterprise_type()
	{
		return $this->belongsTo(\App\Models\EnterpriseType::class, 'enterprise_type_identerprise_type');
	}

	public function vendor()
	{
		return $this->belongsTo(\App\Models\Vendor::class, 'vendor_idvendor');
	}

	public function users()
	{
		return $this->hasMany(\App\User::class, 'client_enterprise_identerprise')->whereIn('idrole', [Constant::ROLE_ENTERPRISE, Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER, Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS]);;
	}

	public function dispatcher()
	{
		return $this
		->hasOne(\App\User::class, 'client_enterprise_identerprise')
		->where("idrole", Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS);
	}

	public function admins()
	{
		return $this
		->hasMany(\App\User::class, 'client_enterprise_identerprise')
		->where("idrole",constant::ROLE_ENTERPRISE);
	}

	public function inspectors()
	{
		return $this->hasMany(\App\Models\Inspector::class, 'client_enterprise_identerprise');
	}
}
