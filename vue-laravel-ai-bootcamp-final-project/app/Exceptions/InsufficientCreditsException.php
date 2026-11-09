<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditsException extends Exception
{
    public function __construct()
    {
        parent::__construct('You need at least one credit to generate content.');
    }
}
