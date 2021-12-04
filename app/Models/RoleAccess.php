<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class RoleAccess
 * 
 * @property int $idrole
 * @property int $idmenu
 * 
 * @property \App\Models\WebMenu $web_menu
 * @property \App\Models\Role $role
 *
 * @package App\Models
 */
class RoleAccess extends Model
{
	use NullToEmptyString;
	
	protected $table = 'role_access';
	protected $primaryKey = 'role_access_id';
	public $timestamps = false;

	protected $casts = [
		'idrole' => 'int',
		'idmenu' => 'int'
	];

	protected $fillable = [
		'idrole',
		'idmenu'
	];

	public function web_menu()
	{
		return $this->belongsTo(\App\Models\WebMenu::class, 'idmenu');
	}

	public function role()
	{
		return $this->belongsTo(\App\Models\Role::class, 'idrole');
	}
}
