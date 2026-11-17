<?php

namespace App\Contracts;

use App\Exceptions\AiProviderException;

interface AiProvider
{
    /**
     * @throws AiProviderException
     */
    public function generate(string $prompt): string;
}
