<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class DriverRequest extends Model
{
	protected $table = 'driver_requests';
	public $timestamps = true;

    protected $fillable = [
		'id',
		'enterprise_id',
		'place_id',
		'note',
		'purpose_time',
		'status',
        'requested_by',
		'created_by',
        'updated_by',
	];
}
