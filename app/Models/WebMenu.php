<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018 05:24:46 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class WebMenu
 * 
 * @property int $idmenu
 * @property string $name
 * @property string $slug
 * @property int $parent_idmenu
 * @property int $static_content_idstatic_content
 * 
 * @property \App\Models\WebMenu $web_menu
 * @property \App\Models\StaticContent $static_content
 * @property \Illuminate\Database\Eloquent\Collection $role_accesses
 * @property \Illuminate\Database\Eloquent\Collection $web_menus
 *
 * @package App\Models
 */
class WebMenu extends Model
{
	use NullToEmptyString;
	
	protected $table = 'web_menu';
	protected $primaryKey = 'idmenu';
	public $timestamps = false;

	protected $casts = [
		'parent_idmenu' => 'int',
	];

	protected $fillable = [
		'name',
		'slug',
		'parent_idmenu',
	];

	public function web_menu()
	{
		return $this->belongsTo(\App\Models\WebMenu::class, 'parent_idmenu');
	}

	public function static_content()
	{
		return $this->belongsTo(\App\Models\StaticContent::class, 'static_content_idstatic_content');
	}

	public function role_accesses()
	{
		return $this->hasMany(\App\Models\RoleAccess::class, 'idmenu');
	}

	public function web_menus()
	{
		return $this->hasMany(\App\Models\WebMenu::class, 'parent_idmenu');
	}
}
