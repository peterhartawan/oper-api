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
        'kategori_id',
        'kode',
        'potongan_fixed',
        'limit_klaim',
        'jumlah_klaim',
        'hari_berlaku'
    ];
}
