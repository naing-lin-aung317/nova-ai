<?php

namespace App\Exceptions;

use Exception;

class GeminiStreamException extends Exception
{
    public function __construct(
        string $message = 'The content provider failed to generate a response.',
        public readonly string $errorCode = 'provider_error',
    ) {
        parent::__construct($message);
    }
}
