<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class TaskTemplate
 *
 * @property int $task_template_id
 * @property string $taskname
 *
 * @property \Illuminate\Database\Eloquent\Collection $orders
 * @property \Illuminate\Database\Eloquent\Collection $tasks
 *
 * @package App\Models
 */
class TaskTemplate extends Model
{
	use NullToEmptyString;

    protected $connection = 'mysql';
	protected $table = 'task_template';
	protected $primaryKey = 'task_template_id';
	public $timestamps = true;

	protected $fillable = [
		'task_template_name',
		'task_template_description',
		'client_enterprise_identerprise',
		'created_by',
		'updated_by',
		'vendor_idvendor'
	];

	public function orders()
	{
		return $this->hasMany(\App\Models\Order::class, 'task_template_task_template_id');
	}

	public function tasks()
	{
		return $this->hasMany(\App\Models\Task::class, 'task_template_id');
	}
	public function vendor()
	{
		return $this->belongsTo(\App\Models\Vendor::class, 'vendor_idvendor');
	}
}
