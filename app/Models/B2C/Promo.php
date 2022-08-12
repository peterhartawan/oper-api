<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $connection = 'b2c';
    protected $table = 'promo';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'kode',
        'potongan_fixed',
        'jumlah_klaim',
        'hari_berlaku'
    ];
}
