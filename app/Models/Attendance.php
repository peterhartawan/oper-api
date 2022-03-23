<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Constants\Constant;
use App\Traits\NullToEmptyString;

class Attendance extends Model
{
	use NullToEmptyString;

	protected $table = 'attendance';
	protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'users_id',
        'clock_in',
        'clock_in_idplaces',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_out',
        'clock_out_idplaces',
        'clock_out_latitude',
        'clock_out_longitude',
        'created_by',
        'updated_by',
        'image_url',
        'remark'
    ];

    public function user()
	{
		return $this->belongsTo(\App\User::class, 'users_id');
    }

	public function driver_profile()
	{
		return $this->belongsTo(\App\Models\Driver::class, 'users_id');
	}
}
