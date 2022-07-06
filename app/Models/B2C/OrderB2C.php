<?php

namespace App\Models\B2C;

use App\Traits\NullToEmptyString;
use Illuminate\Database\Eloquent\Model;

class OrderB2C extends Model
{
    use NullToEmptyString;
    protected $connection = 'b2c';
    // protected $dates = ['time_start', 'time_end'];
    protected $table = 'orders';
    protected $primaryKey = 'id';
	public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'oper_task_order_id',
        'status',
        'link',
        'time_start',
        'time_end',
        'service_type_id',
        'local_city',
        'insurance',
        'stay',
        'notes',
    ];
}
