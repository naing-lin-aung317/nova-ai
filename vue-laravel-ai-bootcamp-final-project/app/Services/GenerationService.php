<?php

namespace App\Services;

use App\Contracts\GeminiClient;
use App\Enums\GenerationStatus;
use App\Exceptions\GeminiStreamException;
use App\Exceptions\GenerationConcurrencyException;
use App\Exceptions\InsufficientCreditsException;
use App\Models\ApiUsageLog;
use App\Models\GenerationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenerationService
{
    public function __construct(
        public WalletService $wallets,
        public GenerationPromptBuilder $prompts,
        public GeminiClient $gemini,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function start(User $user, array $payload, ?string $idempotencyKey = null): GenerationRequest
    {
        if ($idempotencyKey !== null) {
            $existing = GenerationRequest::query()
                ->where('user_id', $user->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null) {
                return $existing;
            }
        }

        $this->assertCanStart($user);

        return DB::transaction(function () use ($user, $payload, $idempotencyKey): GenerationRequest {
            $generation = GenerationRequest::query()->create([
                ...$payload,
                'user_id' => $user->id,
                'idempotency_key' => $idempotencyKey,
                'status' => GenerationStatus::Pending,
                'model' => config('nova.gemini.model'),
            ]);

            $reservationKey = $idempotencyKey ?? ('generation:'.$generation->id);

            $this->wallets->reserveForGeneration($user, $generation, $reservationKey.':reserve');

            return $generation;
        });
    }

    public function regenerate(User $user, GenerationRequest $source): GenerationRequest
    {
        $this->assertCanStart($user);

        return $this->start($user, [
            'content_type' => $source->content_type,
            'language' => $source->language,
            'length' => $source->length,
            'built_in_tone_id' => $source->built_in_tone_id,
            'custom_tone_id' => $source->custom_tone_id,
            'topic' => $source->topic,
            'audience' => $source->audience,
            'keywords' => $source->keywords,
            'call_to_action' => $source->call_to_action,
            'regenerated_from_id' => $source->id,
        ], (string) Str::uuid());
    }

    /**
     * @param  callable(string): void|null  $onChunk
     */
    public function stream(GenerationRequest $generation, ?callable $onChunk = null): void
    {
        $generation->refresh();

        if ($generation->cancelled_at !== null) {
            $this->stop($generation);

            return;
        }

        $generation->forceFill([
            'status' => GenerationStatus::Streaming,
            'stream_started_at' => now(),
            'output_text' => '',
        ])->save();

        $started = microtime(true);
        $success = false;
        $errorCode = null;

        try {
            $system = $this->prompts->systemInstruction($generation);
            $userPrompt = $this->prompts->userPrompt($generation);
            $buffer = '';

            foreach ($this->gemini->streamOrFail($system, $userPrompt) as $chunk) {
                if ($this->shouldStop($generation)) {
                    $this->stop($generation);

                    return;
                }

                $buffer .= $chunk;
                $generation->forceFill(['output_text' => $buffer])->save();

                if ($onChunk !== null) {
                    $onChunk($chunk);
                }
            }

            if ($this->shouldStop($generation)) {
                $this->stop($generation);

                return;
            }

            $generation->forceFill([
                'status' => GenerationStatus::Completed,
                'completed_at' => now(),
                'output_text' => $buffer,
            ])->save();

            $generation->load('reservation');

            if ($generation->reservation !== null) {
                $this->wallets->captureReservation($generation->reservation);
            }

            $success = true;
        } catch (GeminiStreamException $exception) {
            $errorCode = $exception->errorCode;
            $this->fail($generation, $exception->getMessage(), $errorCode);
        } catch (\Throwable $exception) {
            $errorCode = 'server_error';
            $this->fail($generation, 'Generation failed. No credit was charged.', $errorCode);
        } finally {
            ApiUsageLog::query()->create([
                'generation_request_id' => $generation->id,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'success' => $success,
                'error_code' => $errorCode,
            ]);
        }
    }

    public function cancel(GenerationRequest $generation): void
    {
        $generation->forceFill(['cancelled_at' => now()])->save();

        if ($generation->status === GenerationStatus::Pending) {
            $this->stop($generation);
        }
    }

    public function releaseStale(): int
    {
        $released = 0;

        GenerationRequest::query()
            ->whereIn('status', [GenerationStatus::Pending, GenerationStatus::Streaming])
            ->whereHas('reservation', fn ($query) => $query
                ->where('expires_at', '<', now()))
            ->with('reservation')
            ->each(function (GenerationRequest $generation) use (&$released): void {
                $this->fail($generation, 'Generation timed out. No credit was charged.', 'timeout');
                $released++;
            });

        return $released;
    }

    protected function assertCanStart(User $user): void
    {
        $max = (int) config('nova.generation.max_concurrent_per_user', 1);

        $active = GenerationRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [GenerationStatus::Pending, GenerationStatus::Streaming])
            ->count();

        if ($active >= $max) {
            throw new GenerationConcurrencyException;
        }

        $balances = $this->wallets->balancesForUser($user);

        if (($balances['available'] ?? 0) < 1) {
            throw new InsufficientCreditsException;
        }
    }

    protected function shouldStop(GenerationRequest $generation): bool
    {
        $generation->refresh();

        return $generation->cancelled_at !== null;
    }

    protected function stop(GenerationRequest $generation): void
    {
        $generation->load('reservation');

        $generation->forceFill([
            'status' => GenerationStatus::Stopped,
            'completed_at' => now(),
            'failure_reason' => 'Stopped by user. No credit was charged.',
        ])->save();

        if ($generation->reservation !== null) {
            $this->wallets->releaseReservation($generation->reservation, 'Stopped · no charge');
        }
    }

    protected function fail(GenerationRequest $generation, string $reason, ?string $code = null): void
    {
        $generation->load('reservation');

        $generation->forceFill([
            'status' => GenerationStatus::Failed,
            'completed_at' => now(),
            'failure_reason' => $reason,
        ])->save();

        if ($generation->reservation !== null) {
            $this->wallets->releaseReservation($generation->reservation, 'Failed · no charge');
        }
    }
}
