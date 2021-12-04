<?php

/**
 * Created by Ibrahim.
 * Date: Thu, 13 Dec 2018.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\NullToEmptyString;

/**
 * Class StaticContent
 * 
 * @property int $idstatic_content
 * @property string $name
 * @property string $description
 * @property string $content
 * 
 * @property \Illuminate\Database\Eloquent\Collection $web_menus
 *
 * @package App\Models
 */
class StaticContent extends Model
{
	use NullToEmptyString;
	
	protected $table = 'static_content';
	protected $primaryKey = 'idstatic_content';
	public $timestamps = true;

	protected $fillable = [
		'name',
		'description',
		'content',
		'idrole',
		'created_by',
		'updated_by',
		'status'
	];

	public function web_menus()
	{
		return $this->hasMany(\App\Models\WebMenu::class, 'static_content_idstatic_content');
	}
}
