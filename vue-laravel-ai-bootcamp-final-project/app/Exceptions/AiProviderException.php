<?php

namespace App\Exceptions;

use Exception;

class AiProviderException extends Exception
{
    public function __construct(
        string $message,
        public readonly string $errorCode = 'provider_error',
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message);
    }
}
