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
        'jumlah_jam',
        'deskripsi_text',
        'deskripsi_list'
    ];

    public function pricing()
    {
        return $this->hasOne(\App\Models\B2C\Pricing::class,
        'id', 'pricing_id');
    }
}
