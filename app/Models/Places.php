<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class Places extends Model
{
	use NullToEmptyString;
	
	protected $table = 'places';
	public $timestamps = true;

    protected $fillable = [
		'idplaces',
		'name',
		'address',
		'latitude',
		'longitude',
		'identerprise',
		'created_by',
        'updated_by',
	];

	public function CleintEnterprise()
    {
        return $this->hasMany('App\Model\CleintEnterprise');
    }
}
