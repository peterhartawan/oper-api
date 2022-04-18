<?php
namespace App\Http\Controllers;

use App\User;
use App\Models\Vendor;
use Exception;
use Psr\Http\Message\ServerRequestInterface;
use \Laravel\Passport\Http\Controllers\AccessTokenController as ATC;
use App\Services\Response;
use App\Exceptions\ApplicationException;
use App\Constants\Constant;
use App\Models\ClientEnterprise;

class AccessTokenController extends ATC
{
    /**
     * Login user and create token
     *
     * @param  [string] username
     * @param  [string] password
     * @param  [int] client_id
     * @param  [string] client_secret
     * @param  [string] grant_type
     * @return [string] access_token
     */
    public function issueToken(ServerRequestInterface $request)
    {
        $parsedBody = $request->getParsedBody();
        #validate
        if (!isset($parsedBody['grant_type']))
            throw new ApplicationException('errors.failure_require_field', ['field' => 'grant_type']);
        if (!isset($parsedBody['client_id']))
            throw new ApplicationException('errors.failure_require_field', ['field' => 'client_id']);
        if (!isset($parsedBody['client_secret']))
            throw new ApplicationException('errors.failure_require_field', ['field' => 'client_secret']);

        #for refresh token
        if ($parsedBody['grant_type'] == 'refresh_token') {
            $tokenResponse = parent::issueToken($request);
            $content = $tokenResponse->getContent();
            $data = json_decode($content);

            return Response::success($data);
        }

        #validate
        if (!isset($parsedBody['username']))
            throw new ApplicationException('errors.failure_require_field', ['field' => 'username']);
        if (!isset($parsedBody['password']))
            throw new ApplicationException('errors.failure_require_field', ['field' => 'password']);

        #check user
        $username = trim($parsedBody['username']);
        $user = User::where('email', '=', $username)->first();
        if (empty($user))
            throw new ApplicationException('user.user_not_found', ['email' => $username]);

        #check user environment
        $this->validateUserEnvironment($parsedBody, $user);

        # check user status
        $this->validateUserStatus($user);

        #generate token
        $tokenResponse = parent::issueToken($request);

        $content = $tokenResponse->getContent();
        $data = json_decode($content);

        #get error
        if (isset($data->error))
            return Response::error($data->message, 4000);

        $data->is_first_login = $user->is_first_login;

        if ($user->is_first_login){
            $user->is_first_login = false;
            $user->update();
        }

        return Response::success($data, 'messages.success_login');
    }

    private function validateUserEnvironment($parsedBody, $user)
    {
        if (env('CLIENT_SECRET_ANDROID')== trim($parsedBody['client_secret']) || env('CLIENT_SECRET_IOS')== trim($parsedBody['client_secret'])) {
            $vendor = Vendor::where('idvendor', '=', $user->vendor_idvendor)
                        ->where('status','=',Constant::STATUS_ACTIVE)
                        ->first();

            if (empty($vendor))
                throw new ApplicationException('vendors.failed_to_login_suspend_2');

            #check is driver
            if (!in_array($user->idrole, [Constant::ROLE_DRIVER, Constant::ROLE_EMPLOYEE]))
                throw new ApplicationException('errors.unauthorized');
        }
        else {
            #Frontend
            if (!isset($parsedBody['domain']))
                throw new ApplicationException('errors.failure_require_field', ['field' => 'domain']);

            #admin oper
            if  (in_array($parsedBody['domain'], [env('URL_ADMIN_OPER'), env('URL_ADMIN_OPER_NEW')])) {
                if (Constant::ROLE_SUPERADMIN != $user->idrole)
                    throw new ApplicationException('errors.unauthorized');
            }
            #admin vendor
            elseif (in_array($parsedBody['domain'], [env('URL_VENDOR'), env('URL_VENDOR_NEW')])) {
                $vendor = Vendor::where('idvendor', '=', $user->vendor_idvendor)
                        ->where('status','=',Constant::STATUS_ACTIVE)
                        ->first();

                if (empty($vendor))
                    throw new ApplicationException('vendors.failed_to_login_suspend');

                if (!in_array($user->idrole, [Constant::ROLE_VENDOR, Constant::ROLE_DISPATCHER_ENTERPRISE_REGULER, Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS, Constant::ROLE_DISPATCHER_ONDEMAND, Constant::ROLE_EMPLOYEE, Constant::ROLE_VENDOR_SUB]))
                    throw new ApplicationException('errors.unauthorized');

                //jika rolenya dispatcher enterprise plus maka client enterprise harus aktif
                if ($user->idrole == Constant::ROLE_DISPATCHER_ENTERPRISE_PLUS)
                    //cek status client enterprise plus aktif tidak
                    $client       = ClientEnterprise::where('identerprise', $user->client_enterprise_identerprise)
                                    ->where('status',"=",Constant::STATUS_SUSPENDED)
                                    ->first();
                    if (!empty($client))
                        throw new ApplicationException('errors.failed_to_login_suspend');

            }
            #client enterprise
            else {
                $clientEnterprise = ClientEnterprise::where('site_url', trim($parsedBody['domain']))->first();

                #hardcode
                if ($parsedBody['domain'] == 'http://oper-customer-new.festiware.com') {
                    return;
                }

                if (empty($clientEnterprise))
                    throw new ApplicationException('errors.unauthorized');

                if ( $user->idrole != Constant::ROLE_ENTERPRISE )
                    throw new ApplicationException('errors.unauthorized');

                //cek apakah status client
                if ( $clientEnterprise->status == Constant::STATUS_SUSPENDED )
                    throw new ApplicationException('errors.failed_to_login_suspend');

            }

        }
    }

    private function validateUserStatus($user)
    {
        switch ($user->status) {
            case constant::STATUS_INACTIVE:
                throw new ApplicationException('user.account_inactive');
                break;
            case constant::STATUS_SUSPENDED:
                throw new ApplicationException('user.account_suspended');
                break;
            case constant::STATUS_DELETED:
                throw new ApplicationException('user.account_deleted');
                break;
        }
    }
}
