<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class DriverBlastReply extends Model
{
	use NullToEmptyString;

    protected $connection = 'mysql';
	protected $guarded = [];
	protected $table = 'driver_blast_reply';
	protected $primaryKey = 'id';
	public $timestamps = false;

	protected $fillable = [
		'name',
        'phonenumber',
        'address'
	];
}
