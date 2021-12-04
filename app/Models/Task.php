<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class Task
 * 
 * @property int $idtask
 * @property string $name
 * @property string $description
 * 
 * @property \Illuminate\Database\Eloquent\Collection $task_template_has_tasks
 *
 * @package App\Models
 */
class Task extends Model
{
	use NullToEmptyString;

	protected $table = 'task';
	protected $primaryKey = 'idtask';
	public $timestamps = true;


	protected $fillable = [
		'sequence',
		'task_template_id',
		'name',
		'description', 
		'status', 
		'is_required', 
		'is_need_photo', 
		'is_need_inspector_validation', 
		'latitude', 
		'longitude',
		'created_by',
		'updated_by',
        'location_name'
	];

	protected $casts = [
        'is_required' => 'boolean',
        'is_need_photo' => 'boolean',
        'is_need_inspector_validation' => 'boolean',
    ];

	public function task_template_has_tasks()
	{
		return $this->hasMany(\App\Models\TaskTemplateHasTask::class, 'task_idtask');
	}
}
