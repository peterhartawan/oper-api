<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceRequest extends Model
{
    protected $table = 'attendance_request';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'datetime',
        'latitude',
        'longitude',
        'remark',
        'created_by',
        'approved_by'
    ];

    public function driver()
    {
        return $this->hasOne(Driver::class, 'iddriver', 'created_by');
    }
}
