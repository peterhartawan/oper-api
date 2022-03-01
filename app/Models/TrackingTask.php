<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\NullToEmptyString;

class TrackingTask extends Model
{

	use NullToEmptyString;
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tracking_task';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['idorder','latitude','longitude','created_by','status','updated_by'];

    public function order(){
        return $this->hasMany(\App\Models\Order::class, 'idorder', 'idorder');
    }
}
