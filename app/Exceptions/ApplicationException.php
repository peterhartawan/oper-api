<?php

namespace App\Exceptions;

use Exception;

use App\Http\Helpers\GlobalHelper;

class ApplicationException extends Exception {
    
    protected $mStatusCode;

    public function __construct(string $resource_lang_key, array $params = [], int $code = 0, int $status_code = 400, Throwable $previous = NULL)
    {
        $resource = GlobalHelper::getResourceError($resource_lang_key, $params, $code, $status_code);
        
        parent::__construct($resource['message'], $resource['code'], $previous);

        $this->mStatusCode = $resource['status_code'];
    }

    public function getStatusCode()
    {
        return $this->mStatusCode;
    }
}