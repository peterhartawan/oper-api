<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as BaseResponse;
use App\Http\Helpers\GlobalHelper;

class Response
{

    /**
     * @param array | Illuminate\Database\Eloquent\Model $data
     * @param int $status HTTP status code
     * @param array $extraHeaders
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public static function success($data = [], $message = 'messages.success', $params = [], $headers = [], $options = 0)
    {
        return self::getResponse($data, TRUE, trans($message, $params), 200, 200, $headers, $options);
    }

    public static function error($error_message, $error_code, $http_status_code = 422, Exception $exception = NULL)
    {
        $content = [];
        $error_message_new = $error_message;
        if (empty($error_message))
        {
            if (array_key_exists($http_status_code, BaseResponse::$statusTexts))
                $error_message_new = BaseResponse::$statusTexts[$http_status_code];
        }
        
        if ($exception !== NULL)
        {
            if ($exception instanceof ValidationException)
                $error_message_new = $exception->errors()->first();
        }
        
        if (env('APP_DEBUG', FALSE) && !empty($exception))
            $content['debug'] = [
                'code'      => $exception->getCode(),
                'message'   => $exception->getMessage(),
                'file'      => $exception->getFile(),
                'line'      => $exception->getLine(),
                'trace'     => collect($exception->getTrace())->map(function ($trace) {
                    return Arr::except($trace, ['args']);
                })->all()];
        
        return self::getResponse($content, FALSE, $error_message_new, $error_code, $http_status_code);
    }

    protected static function getResponse($data, $status = TRUE, $message = '', $error_code = 200, $http_code = 200, $headers = [], $options = 0)
    {
        $response = NULL;
        try
        {
            $result = [
                'status'        => $status,
                'code'          => $error_code,
                'message'       => $message];

            if (!empty($data)) {
                if (TRUE === $status)
                    $result['data'] = $data;
                else 
                    $result = array_merge($result, $data);
            }
            
            $response = response()->json($result, $http_code, $headers, $options);
        }
        catch (InvalidArgumentException $ex)
        {
            $resource = GlobalHelper::getResourceError('errors.failed_to_create_response');
            $response = self::error($resource['message'], $resource['status_code'], $ex);
        }
        
        return $response;
    }
}