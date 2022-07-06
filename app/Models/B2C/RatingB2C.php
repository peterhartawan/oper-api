<?php

namespace App\Models\B2C;

use App\Traits\NullToEmptyString;
use Illuminate\Database\Eloquent\Model;

class RatingB2C extends Model
{
    use NullToEmptyString;
    protected $connection = 'b2c';
    // protected $dates = ['time_start', 'time_end'];
    protected $table = 'rating';
    protected $primaryKey = 'id';
	public $timestamps = true;

    protected $fillable = [
        'b2c_order_id',
        'rating',
        'comment',
    ];
}
