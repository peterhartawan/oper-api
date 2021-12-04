<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use Validator;
use App\Services\Response;
use App\Exceptions\ApplicationException;

class AuthController extends Controller
{
    /**
     * Logout user (Revoke the token)
     *
     * @return [string] message
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return Response::success([
            'username'     => $request->user()->email,
        ], 'messages.success_logout');
    }
    
}