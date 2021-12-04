<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class MobileCheckUpdate extends Model
{
	use NullToEmptyString;
	
	protected $guarded = [];
	protected $table = 'mobile_version';
	protected $primaryKey = 'id';
	public $timestamps = true;

	protected $casts = [
		'version' => 'int',
	];

	protected $fillable = [
		'version',
		'created_by',
        'updated_by'
	];
}
