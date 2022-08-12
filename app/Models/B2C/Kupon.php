<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class Kupon extends Model
{
    protected $connection = 'b2c';
    protected $table = 'kupon';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'promo_id',
        'customer_id',
        'jumlah_kupon',
        'waktu_berakhir'
    ];

    public function promo()
    {
        return $this->hasOne(\App\Models\B2C\Promo::class, 'id', 'promo_id');
    }
}
