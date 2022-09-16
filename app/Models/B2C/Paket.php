<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class Paket extends Model
{
    protected $connection = 'b2c';
    protected $table = 'paket';
    protected $primaryKey = 'id';
	public $timestamps = false;

    protected $fillable = [
        'pricing_id',
        'deskripsi_text',
        'deskripsi_list'
    ];
}
