<?php

namespace App\Support;

use App\Exceptions\AiProviderException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class AiHttpErrors
{
    public static function fromResponse(Response $response, string $providerLabel): AiProviderException
    {
        $providerMessage = self::extractMessage($response->body());
        $status = $response->status();

        if (self::isRegionRestriction($providerMessage)) {
            Log::warning('AI provider region restriction', [
                'provider' => $providerLabel,
                'http_status' => $status,
            ]);

            return new AiProviderException(
                'region_restricted',
                'region_restricted',
                $status,
            );
        }

        $classified = self::classifyByHttpStatus($status, $providerMessage);

        if ($classified !== null) {
            Log::warning('AI provider classified error', [
                'provider' => $providerLabel,
                'http_status' => $status,
                'error_code' => $classified,
            ]);

            return new AiProviderException($classified, $classified, $status);
        }

        $message = $providerMessage !== null && $providerMessage !== ''
            ? sprintf('The AI provider returned HTTP %d: %s', $status, $providerMessage)
            : sprintf('The AI provider returned HTTP %d.', $status);

        Log::warning('AI provider error response', [
            'provider' => $providerLabel,
            'http_status' => $status,
            'provider_message' => $providerMessage,
        ]);

        return new AiProviderException($message, 'provider_error', $status);
    }

    public static function userFailureReason(AiProviderException $exception): string
    {
        if (in_array($exception->errorCode, self::friendlyFailureCodes(), true)) {
            return $exception->errorCode;
        }

        return $exception->getMessage();
    }

    /**
     * @return list<string>
     */
    public static function friendlyFailureCodes(): array
    {
        return [
            'region_restricted',
            'missing_api_key',
            'invalid_api_key',
            'missing_model',
            'unsupported_model',
            'provider_error',
            'generation_failed',
            'timeout',
            'stopped',
        ];
    }

    protected static function classifyByHttpStatus(int $status, ?string $providerMessage): ?string
    {
        if ($status === 401) {
            return 'invalid_api_key';
        }

        if ($status === 404) {
            return 'unsupported_model';
        }

        return null;
    }

    protected static function extractMessage(string $body): ?string
    {
        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            return null;
        }

        $paths = [
            'error.message',
            '0.error.message',
            'message',
            'error',
        ];

        foreach ($paths as $path) {
            $value = data_get($decoded, $path);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    protected static function isRegionRestriction(?string $message): bool
    {
        if ($message === null || $message === '') {
            return false;
        }

        $lower = strtolower($message);

        return str_contains($lower, 'user location is not supported')
            || str_contains($lower, 'not supported for the api use')
            || str_contains($lower, 'location is not supported');
    }
}
