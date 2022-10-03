<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class OpsHours extends Model
{
    protected $connection = 'b2c';
    protected $table = 'ops_hours';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nama',
        'jam'
    ];
}
