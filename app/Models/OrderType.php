<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderType extends Model
{
	protected $table = 'order_type';
	protected $primaryKey = 'idorder_type';
	public $timestamps = true;
}
