<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class ChangeEmail extends Model
{
	use NullToEmptyString;
    
	protected $table = 'change_email';
	protected $primaryKey = 'idchange_email';
    public $timestamps = true;

    protected $fillable = [
        'old_email', 'new_email','token'
    ];
}
