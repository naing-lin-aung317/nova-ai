<?php

use App\Support\AiHttpErrors;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

uses(TestCase::class);

test('maps gemini region restriction to region_restricted code', function () {
    $body = '[{"error":{"code":400,"message":"User location is not supported for the API use.","status":"FAILED_PRECONDITION"}}]';

    $response = new Response(new GuzzleHttp\Psr7\Response(400, [], $body));

    $exception = AiHttpErrors::fromResponse($response, 'gemini');

    expect($exception->errorCode)->toBe('region_restricted')
        ->and(AiHttpErrors::userFailureReason($exception))->toBe('region_restricted');
});

test('maps unknown provider errors to generic provider_error', function () {
    $body = '{"error":{"code":400,"message":"Bad request","status":"INVALID_ARGUMENT"}}';

    $response = new Response(new GuzzleHttp\Psr7\Response(400, [], $body));

    $exception = AiHttpErrors::fromResponse($response, 'gemini');

    expect($exception->errorCode)->toBe('provider_error')
        ->and(AiHttpErrors::userFailureReason($exception))->toBe('provider_error');
});

test('maps http 401 to invalid_api_key', function () {
    $body = '{"error":{"code":401,"message":"API key not valid. Please pass a valid API key.","status":"UNAUTHENTICATED"}}';

    $response = new Response(new GuzzleHttp\Psr7\Response(401, [], $body));

    $exception = AiHttpErrors::fromResponse($response, 'gemini');

    expect($exception->errorCode)->toBe('invalid_api_key')
        ->and(AiHttpErrors::userFailureReason($exception))->toBe('invalid_api_key');
});

test('maps http 404 to unsupported_model', function () {
    $body = '{"error":{"code":404,"message":"models/bad-id is not found for API version v1beta.","status":"NOT_FOUND"}}';

    $response = new Response(new GuzzleHttp\Psr7\Response(404, [], $body));

    $exception = AiHttpErrors::fromResponse($response, 'gemini');

    expect($exception->errorCode)->toBe('unsupported_model')
        ->and(AiHttpErrors::userFailureReason($exception))->toBe('unsupported_model');
});
