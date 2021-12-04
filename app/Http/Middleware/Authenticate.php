<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Contracts\Auth\Factory as Auth;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use App\Constants\RoleAccess;
use Route;

class Authenticate
{
    /**
     * The authentication factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $this->authenticate($request, $guards);

        switch (auth()->guard('api')->user()->idrole) {
            case Constant::ROLE_SUPERADMIN:
                $this->access_permission(RoleAccess::SUPERADMIN);
                break;
            case Constant::ROLE_VENDOR:
                $this->access_permission(RoleAccess::VENDOR);
                break;
            case Constant::ROLE_ENTERPRISE:
                $this->access_permission(RoleAccess::ENTERPRISE);
                break;
            case Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER:
                $this->access_permission(RoleAccess::DISPATCHER_ENTERPRISE_REGULAR);
                break;
            case Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS:
                $this->access_permission(RoleAccess::DISPATCHER_ENTERPRISE_PLUS);
                break;
            case Constant::ROLE_DISPATCHER_ONDEMAND:
                $this->access_permission(RoleAccess::DISPATCHER_ONDEMAND);
                break;
            case Constant::ROLE_DRIVER:
                $this->access_permission(RoleAccess::DRIVER);
                break;
            case Constant::ROLE_EMPLOYEE:
                $this->access_permission(RoleAccess::EMPLOYEE);
                break;

            default:
            throw new ApplicationException('errors.access_denied');
                break;
        }

        return $next($request);
    }

    protected function access_permission($role){

        $currentAction = \Route::currentRouteAction();

        if(!$currentAction){
            throw new ApplicationException('errors.access_denied');
        }

        list($controller, $method) = explode('@', $currentAction);
        $controller = preg_replace('/.*\\\/', '', $controller);

        if ($controller == "TestingController") {
            return;
        }

        if( !array_key_exists($controller,$role)){
            throw new ApplicationException('errors.access_denied');
        }

        if (!is_array($role[$controller]) && $role[$controller] != "all")
            throw new ApplicationException('errors.access_denied');

        if (is_array($role[$controller]) && !in_array($method, $role[$controller]))
            throw new ApplicationException('errors.access_denied');
    }


    /**
     * Determine if the user is logged in to any of the given guards.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $guards
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function authenticate($request, array $guards)
    {
        if (empty($guards)) {
            $guards = [null];
        }

        foreach ($guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        throw new ApplicationException('errors.unauthorized');
    }
}
