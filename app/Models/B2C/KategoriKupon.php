<?php

namespace App\Models\B2C;

use Illuminate\Database\Eloquent\Model;

class KategoriKupon extends Model
{
    protected $connection = 'b2c';
    protected $table = 'kategori_kupon';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'nama',
    ];
}
