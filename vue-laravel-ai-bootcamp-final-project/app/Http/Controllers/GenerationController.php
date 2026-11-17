<?php

namespace App\Http\Controllers;

use App\Enums\GenerationStatus;
use App\Exceptions\GenerationConcurrencyException;
use App\Exceptions\InsufficientCreditsException;
use App\Http\Requests\StoreGenerationRequest;
use App\Http\Requests\UpdateGenerationOutputRequest;
use App\Models\GenerationRequest;
use App\Services\GenerationService;
use App\Support\GenerationPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerationController extends Controller
{
    public function __construct(public GenerationService $generations) {}

    public function store(StoreGenerationRequest $request): JsonResponse
    {
        try {
            $generation = $this->generations->start(
                $request->user(),
                $request->validated(),
                $request->idempotencyKey(),
            );
        } catch (InsufficientCreditsException $exception) {
            return response()->json([
                'code' => 'insufficient_credits',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (GenerationConcurrencyException $exception) {
            return $this->concurrencyLimitResponse($request);
        }

        return response()->json([
            'id' => $generation->id,
            'status' => $generation->status->value,
            'stream_url' => route('generations.stream', $generation),
        ], 201);
    }

    public function show(GenerationRequest $generation): JsonResponse
    {
        $this->authorize('view', $generation);

        return response()->json(GenerationPresenter::apiPayload($generation));
    }

    public function update(
        UpdateGenerationOutputRequest $request,
        GenerationRequest $generation,
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $generation);

        if ($generation->status !== GenerationStatus::Completed) {
            abort(422, 'Only completed generations can be edited.');
        }

        $generation->forceFill([
            'output_text' => $request->validated('output_text'),
        ])->save();

        if ($request->wantsJson()) {
            return response()->json(GenerationPresenter::apiPayload($generation));
        }

        return redirect()
            ->route('history.show', $generation)
            ->with('status', 'saved');
    }

    public function destroy(Request $request, GenerationRequest $generation): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $generation);

        if (! GenerationPresenter::canDelete($generation)) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Cancel or wait for the active generation to finish before deleting.',
                ], 409);
            }

            abort(409, 'Cancel or wait for the active generation to finish before deleting.');
        }

        $generation->delete();

        if ($request->wantsJson()) {
            return response()->json(['deleted' => true]);
        }

        return redirect()->route('history');
    }

    public function stream(GenerationRequest $generation): StreamedResponse
    {
        $this->authorize('stream', $generation);

        return response()->stream(function () use ($generation): void {
            $this->generations->stream($generation, function (string $chunk): void {
                echo 'data: '.json_encode(['chunk' => $chunk], JSON_THROW_ON_ERROR)."\n\n";

                if (ob_get_level() > 0) {
                    ob_flush();
                }

                flush();
            });

            $generation->refresh();

            echo 'data: '.json_encode([
                'done' => true,
                'status' => $generation->status->value,
                'charged' => $generation->status === GenerationStatus::Completed,
            ], JSON_THROW_ON_ERROR)."\n\n";
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function cancel(GenerationRequest $generation): JsonResponse
    {
        $this->authorize('cancel', $generation);

        $this->generations->cancel($generation);

        $generation->refresh();

        return response()->json(GenerationPresenter::apiPayload($generation));
    }

    public function regenerate(GenerationRequest $generation, Request $request): JsonResponse
    {
        $this->authorize('regenerate', $generation);

        try {
            $next = $this->generations->regenerate($request->user(), $generation);
        } catch (InsufficientCreditsException $exception) {
            return response()->json([
                'code' => 'insufficient_credits',
                'message' => $exception->getMessage(),
            ], 422);
        } catch (GenerationConcurrencyException $exception) {
            return $this->concurrencyLimitResponse($request);
        }

        return response()->json([
            'id' => $next->id,
            'status' => $next->status->value,
            'stream_url' => route('generations.stream', $next),
        ], 201);
    }

    protected function concurrencyLimitResponse(Request $request): JsonResponse
    {
        $active = $this->generations->activeGenerationForUser($request->user());

        return response()->json([
            'code' => 'concurrency_limit',
            'message' => (new GenerationConcurrencyException)->getMessage(),
            'active_generation_id' => $active?->id,
        ], 429);
    }
}
