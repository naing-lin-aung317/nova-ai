import type { GenerationCreatePayload, StreamDoneEvent } from '@/types/generation';

export class GenerationApiError extends Error {
    code?: string;

    activeGenerationId?: number;

    constructor(message: string, code?: string, activeGenerationId?: number) {
        super(message);
        this.code = code;
        this.activeGenerationId = activeGenerationId;
    }
}

function csrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function jsonHeaders(idempotencyKey?: string): HeadersInit {
    const headers: Record<string, string> = {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
    };

    if (idempotencyKey) {
        headers['Idempotency-Key'] = idempotencyKey;
    }

    return headers;
}

export async function createGeneration(
    payload: GenerationCreatePayload,
    idempotencyKey: string = crypto.randomUUID(),
): Promise<{ id: number; status: string; stream_url: string }> {
    const response = await fetch('/generations', {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(idempotencyKey),
        body: JSON.stringify(payload),
    });

    const body = (await response.json()) as {
        id?: number;
        stream_url?: string;
        status?: string;
        code?: string;
        message?: string;
        active_generation_id?: number;
    };

    if (! response.ok) {
        throw new GenerationApiError(
            body.message ?? 'Request failed',
            body.code,
            body.active_generation_id,
        );
    }

    return {
        id: body.id as number,
        status: body.status as string,
        stream_url: body.stream_url as string,
    };
}

export async function cancelGeneration(id: number): Promise<void> {
    const response = await fetch(`/generations/${id}/cancel`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
    });

    if (! response.ok) {
        const body = (await response.json().catch(() => ({}))) as { message?: string };

        throw new GenerationApiError(body.message ?? 'Cancel failed');
    }
}

export async function regenerateGeneration(
    id: number,
): Promise<{ id: number; stream_url: string }> {
    const response = await fetch(`/generations/${id}/regenerate`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
    });

    const body = (await response.json()) as {
        id?: number;
        stream_url?: string;
        code?: string;
        message?: string;
        active_generation_id?: number;
    };

    if (! response.ok) {
        throw new GenerationApiError(
            body.message ?? 'Request failed',
            body.code,
            body.active_generation_id,
        );
    }

    return {
        id: body.id as number,
        stream_url: body.stream_url as string,
    };
}

export async function fetchGeneration(id: number): Promise<{
    status: string;
    output_text: string | null;
    failure_reason: string | null;
    charged: boolean;
    no_charge: boolean;
}> {
    const response = await fetch(`/generations/${id}`, {
        credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });

    if (! response.ok) {
        throw new GenerationApiError('Could not load generation');
    }

    return (await response.json()) as {
        status: string;
        output_text: string | null;
        failure_reason: string | null;
        charged: boolean;
        no_charge: boolean;
    };
}

export async function streamGeneration(
    streamUrl: string,
    onChunk: (chunk: string) => void,
    signal?: AbortSignal,
): Promise<StreamDoneEvent> {
    const response = await fetch(streamUrl, {
        credentials: 'same-origin',
        headers: { Accept: 'text/event-stream', 'X-Requested-With': 'XMLHttpRequest' },
        signal,
    });

    if (! response.ok) {
        throw new GenerationApiError('Stream failed');
    }

    const reader = response.body?.getReader();

    if (! reader) {
        throw new GenerationApiError('Stream unsupported');
    }

    const decoder = new TextDecoder();
    let pending = '';

    while (true) {
        const { done, value } = await reader.read();

        if (done) {
            break;
        }

        pending += decoder.decode(value, { stream: true });

        let splitAt = pending.indexOf('\n\n');

        while (splitAt !== -1) {
            const block = pending.slice(0, splitAt);
            pending = pending.slice(splitAt + 2);
            const line = block
                .split('\n')
                .find((row) => row.startsWith('data: '));

            if (line) {
                const payload = JSON.parse(line.slice(6)) as {
                    chunk?: string;
                    done?: boolean;
                    status?: string;
                    charged?: boolean;
                };

                if (payload.chunk !== undefined) {
                    onChunk(payload.chunk);
                }

                if (payload.done) {
                    return {
                        done: true,
                        status: payload.status ?? 'completed',
                        charged: Boolean(payload.charged),
                    };
                }
            }

            splitAt = pending.indexOf('\n\n');
        }
    }

    throw new GenerationApiError('Stream ended unexpectedly');
}
