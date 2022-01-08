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
        'number_of_drivers',
		'note',
		'purpose_time',
		'status',
        'requested_by',
		'created_by',
        'updated_by',
	];

    public function enterprise()
    {
        return $this->hasOne(ClientEnterprise::class, 'identerprise', 'enterprise_id');
    }

    public function place()
    {
        return $this->hasOne(Places::class, 'idplaces', 'place_id');
    }
}
