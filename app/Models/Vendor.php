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
class Vendor extends Model
{
	use NullToEmptyString;

	protected $table = 'vendor';
	protected $primaryKey = 'idvendor';
	public $timestamps = false;

	protected $fillable = [
		'idvendor',
		'name',
		'email',
		'office_phone_number',
		'office_address',
		'pic_name',
		'pic_mobile_number',
		'pic_email',
		'show_employee_price',
		'created_by',
        'updated_by',
        'status'
	];


	public function vendor_handle_client()
	{
		return $this->hasMany(\App\Models\VendorHandleClient::class, 'vendor_idvendor');
	}

	public function clients()
	{
		return $this->belongsToMany('App\Models\VendorHandleClient', 'vendor_handle_client', 'vendor_idvendor', 'client_enterprise_identerprise');
	}

	public function dispatchers()
	{
		return $this->hasMany(\App\Models\Dispatcher::class, 'vendor_idvendor');
	}

	public function users()
	{
		return $this->hasMany(\App\User::class, 'vendor_idvendor');
	}

	public function admins()
	{
		return $this->hasMany(\App\User::class, 'vendor_idvendor')->where('idrole', 2);
	}

    public function enterprises()
    {
        return $this->hasMany(ClientEnterprise::class, 'vendor_idvendor', 'idvendor');
    }
}
