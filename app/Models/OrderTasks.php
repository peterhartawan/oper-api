<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

class OrderTasks extends Model
{
    use NullToEmptyString;

	protected $table = 'order_tasks';
	protected $primaryKey = 'idordertask';
    public $timestamps = true;
    
	protected $fillable = [
        'name', 
        'description', 
        'order_idorder', 
        'attachment_url', 
        'order_task_status', 
        'created_by', 
        'sequence',
        'updated_by', 
        'status', 
        'submit_latitude', 
        'submit_longitude',
        'latitude', 
        'longitude', 
        'is_required',
        'is_need_photo',
        'is_need_inspector_validation',
        'last_update_status',
        'location_name'
    ];

    protected $casts = [
        'is_required' => 'boolean',
        'is_need_photo' => 'boolean',
        'is_need_inspector_validation' => 'boolean',
    ];

	public function order()
	{
		return $this->belongsTo(\App\Models\Order::class, 'order_idorder')->with("enterprise","dispatcher","driver");
    }
    
}
