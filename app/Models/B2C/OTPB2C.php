<?php

namespace App\Models\B2C;

use App\Traits\NullToEmptyString;
use Illuminate\Database\Eloquent\Model;

class OTPB2C extends Model
{
    use NullToEmptyString;
    protected $connection = 'b2c';
    protected $table = 'otp';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'phone',
        'code',
        'status'
    ];
}
