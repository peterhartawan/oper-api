<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class ApplyOrderB2C extends Model
{
    protected $connection = 'b2c';
    protected $table = 'apply_order';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'link',
        'driver_userid',
        'sequence'
    ];
}
