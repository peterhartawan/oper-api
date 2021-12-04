<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Role
 * 
 * @property int $idrole
 * @property string $rolename
 * @property string $roletype
 * 
 * @property \App\Models\RoleAccess $role_access
 * @property \Illuminate\Database\Eloquent\Collection $users
 *
 * @package App\Models
 */
class Role extends Model
{
	protected $table = 'role';
	protected $primaryKey = 'idrole';
	public $timestamps = false;

	protected $fillable = [
		'rolename',
		'roletype'
	];

	public function role_access()
	{
		return $this->hasOne(\App\Models\RoleAccess::class, 'idrole');
	}

	public function users()
	{
		return $this->hasMany(\App\Models\User::class, 'idrole');
	}
}
