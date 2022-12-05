<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class Pricing extends Model
{
    protected $connection = 'b2c';
    protected $table = 'pricing';
    protected $primaryKey = 'id';
	public $timestamps = false;

    protected $fillable = [
        'nama',
        'harga',
    ];
}
