<?php

namespace App\Exceptions;

use Exception;

class GenerationConcurrencyException extends Exception
{
    public function __construct()
    {
        parent::__construct('Finish or cancel your current generation before starting another.');
    }
}
