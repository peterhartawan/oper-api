<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 19 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class VendorHandleClient extends Model
{
	use NullToEmptyString;
	
    protected $table = 'vendor_handle_client';
	public $incrementing = false;
	public $timestamps = true;

	protected $casts = [
		'vendor_idvendor' => 'int',
		'client_enterprise_identerprise' => 'int'
	];

	protected $fillable = [
		'vendor_idvendor',
		'client_enterprise_identerprise'
	];

	public function client_enterprise()
	{
		return $this->belongsTo(\App\Models\ClientEnterprise::class, 'client_enterprise_identerprise');
	}

	public function vendor()
	{
		return $this->belongsTo(\App\Models\Vendor::class, 'vendor_idvendor');
	}
}
