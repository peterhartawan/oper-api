<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class Inspector extends Model
{
	use NullToEmptyString;
	protected $table = 'inspector';
	protected $primaryKey = 'idinspector';
	public $timestamps = true;

   protected $fillable = [
		'client_enterprise_identerprise',
		'name',
		'phonenumber',
		'created_by', 
		'updated_by', 
		'status'
	];

	public function enterprise()
	{
		return $this->belongsTo(\App\Models\ClientEnterprise::class, 'client_enterprise_identerprise');
	}
}
