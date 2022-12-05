<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class MonthlyBaseOrder extends Model
{
    protected $connection = 'b2c';
    protected $table = 'monthly_base_order';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'client_vehicle_license',
        'user_fullname',
        'user_phonenumber',
        'vehicle_brand_id',
        'vehicle_type',
        'vehicle_transmission',
        'message',
        'origin_name',
        'origin_latitude',
        'driver_userid',
        'times_a_week'
    ];
}
