<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class Vehicles extends Model
{
	use NullToEmptyString;
	
	protected $table = 'vehicles';
	public $timestamps = true;

    protected $fillable = [
		'idvehicles',
		'brand',
		'type',
		'transmission',
		'year',
		'color',
		'created_by',
    'updated_by',
	];
}
