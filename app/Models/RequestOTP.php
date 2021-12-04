<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class RequestOTP extends Model
{
    use NullToEmptyString;
    
	protected $table = 'request_otp';
	protected $primaryKey = 'idrequest_otp';
    public $timestamps = true;

    protected $fillable = [
        'phonenumber', 'otp','idordertask','retry'
    ];
}
