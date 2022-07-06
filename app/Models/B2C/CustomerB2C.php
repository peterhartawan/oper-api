<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class CustomerB2C extends Model
{
    protected $connection = 'b2c';
    protected $table = 'customers';
    protected $primaryKey = 'id';
	public $timestamps = true;

    protected $fillable = [
        'phone',
        'email',
        'fullname',
        'gender',
    ];
}
