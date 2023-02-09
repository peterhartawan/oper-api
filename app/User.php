<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Passport\HasApiTokens;
use App\Constants\Constant;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    //locking the connection for cross-server queries
    protected $connection = 'mysql';

    protected $casts = [
		'idrole' => 'int',
		'vendor_idvendor' => 'int',
		'dispatcher_iddispatcher' => 'int',
		'client_enterprise_identerprise' => 'int'
	];

	protected $dates = [
		'email_verified_at'
	];

	protected $hidden = [
		'password',
		'remember_token'
	];

	protected $fillable = [
		'name',
		'email',
		'email_verified_at',
		'password',
		'remember_token',
		'phonenumber',
		'idrole',
		'vendor_idvendor',
		'dispatcher_iddispatcher',
		'client_enterprise_identerprise',
		'is_first_login',
		'profile_picture',
		'profil_picture_2',
		'created_by',
    	'updated_by',
		'status'
	];

	public function createNewUser(){

	}

	public function enterprise()
	{
		return $this->hasOne('App\Models\ClientEnterprise', 'identerprise', 'client_enterprise_identerprise')
		->with("enterprise_type");
	}

	public function vendor()
	{
		return $this->belongsTo(\App\Models\Vendor::class, 'vendor_idvendor');
	}

	public function role()
	{
		return $this->belongsTo(\App\Models\Role::class, 'idrole');
	}

	public function driver_profile()
	{
		return $this->hasOne('App\Models\Driver', 'users_id', 'id');
	}

	public function employee_profile()
	{
		return $this->hasOne('App\Models\Employee', 'users_id', 'id');
	}

	public function dispatcher_profile()
	{
		return $this->hasOne('App\Models\Dispatcher', 'users_id', 'id');
	}

	public function dispatcher()
	{
		return $this->hasOne(\App\User::class, 'vendor_idvendor', 'vendor_idvendor')
					->where('idrole', constant::ROLE_DISPATCHER_ENTERPRISE_PLUS);
	}

    public function attendance()
    {
        return $this->hasOne(\App\Attendance::class, 'users_id', 'id');
    }
}
