<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class FirstPromo extends Model
{
    protected $connection = 'b2c';
    protected $table = 'first_promo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'phone',
    ];
}
