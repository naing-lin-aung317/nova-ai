<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    GenerationApiError,
    cancelGeneration,
    createGeneration,
    fetchGeneration,
    regenerateGeneration,
    streamGeneration,
} from '@/lib/generationStream';
import type { SharedWallet } from '@/types/wallet';
import type { GenerationCreatePayload, ToneOption } from '@/types/generation';

defineOptions({ layout: AppLayout });

type ResumeState = {
    id: number;
    status: string;
    output_text: string | null;
};

type GeneratorPrefill = {
    content_type: string | null;
    topic: string | null;
};

const props = defineProps<{
    builtInTones: ToneOption[];
    customTones: ToneOption[];
    contentTypes: string[];
    contentLanguages: string[];
    contentLengths: string[];
    resume: ResumeState | null;
    briefMaxLength: number;
    prefill: GeneratorPrefill;
    lengthHintsByType: Record<string, Record<string, string>>;
}>();

const { t } = useI18n();
const page = usePage();

const wallet = computed(
    () => (page.props as { wallet?: SharedWallet | null }).wallet ?? null,
);

const contentType = ref(props.contentTypes[0] ?? 'facebook_post');
const contentLanguage = ref('en');
const contentLength = ref('medium');
const toneKind = ref<'built_in' | 'custom'>('built_in');
const builtInToneId = ref<number | null>(props.builtInTones[0]?.id ?? null);
const customToneId = ref<number | null>(props.customTones[0]?.id ?? null);
const brief = ref(props.prefill.topic ?? '');

if (props.prefill.content_type && props.contentTypes.includes(props.prefill.content_type)) {
    contentType.value = props.prefill.content_type;
}

function toneKey(kind: 'built_in' | 'custom', id: number): string {
    return `${kind}:${id}`;
}

const selectedToneKey = ref(
    props.builtInTones[0]
        ? toneKey('built_in', props.builtInTones[0].id)
        : props.customTones[0]
          ? toneKey('custom', props.customTones[0].id)
          : '',
);

watch(selectedToneKey, (key) => {
    if (! key) {
        return;
    }

    const [kind, idPart] = key.split(':');
    const id = Number(idPart);

    if (kind === 'built_in') {
        toneKind.value = 'built_in';
        builtInToneId.value = id;
        customToneId.value = null;
    } else if (kind === 'custom') {
        toneKind.value = 'custom';
        customToneId.value = id;
        builtInToneId.value = null;
    }
});

const phase = ref<'idle' | 'generating' | 'done' | 'failed' | 'no_credits'>('idle');
const outputText = ref('');
const activeGenerationId = ref<number | null>(null);
const errorMessage = ref('');
const formErrors = ref<string[]>([]);
const abortController = ref<AbortController | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const hasCredits = computed(() => (wallet.value?.available ?? 0) >= 1);
const canGenerate = computed(
    () => hasCredits.value && phase.value !== 'generating' && brief.value.trim() !== '',
);

const lengthHint = computed(() => {
    const byType = props.lengthHintsByType[contentType.value]
        ?? props.lengthHintsByType.default;

    return byType?.[contentLength.value] ?? '';
});

const outputEmpty = computed(() => outputText.value.trim() === '');

function buildPayload(): GenerationCreatePayload {
    const payload: GenerationCreatePayload = {
        content_type: contentType.value,
        language: contentLanguage.value,
        length: contentLength.value,
        topic: brief.value.trim(),
    };

    if (toneKind.value === 'built_in' && builtInToneId.value !== null) {
        payload.built_in_tone_id = builtInToneId.value;
    } else if (toneKind.value === 'custom' && customToneId.value !== null) {
        payload.custom_tone_id = customToneId.value;
    }

    return payload;
}

function stopPolling(): void {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

function refreshWallet(): void {
    router.reload({ only: ['wallet'] });
}

async function runStream(streamUrl: string, generationId: number): Promise<void> {
    phase.value = 'generating';
    outputText.value = '';
    errorMessage.value = '';
    activeGenerationId.value = generationId;

    const controller = new AbortController();
    abortController.value = controller;

    pollTimer = setInterval(async () => {
        try {
            const data = await fetchGeneration(generationId);
            outputText.value = data.output_text ?? outputText.value;

            if (data.status !== 'pending' && data.status !== 'streaming') {
                stopPolling();
            }
        } catch {
            /* ignore polling errors */
        }
    }, 2500);

    try {
        const done = await streamGeneration(
            streamUrl,
            (chunk) => {
                outputText.value += chunk;
            },
            controller.signal,
        );

        stopPolling();

        if (done.status === 'completed') {
            phase.value = 'done';
        } else if (done.status === 'failed' || done.status === 'stopped') {
            phase.value = 'failed';
            const latest = await fetchGeneration(generationId);
            errorMessage.value =
                latest.failure_reason ?? t('generator.failedDefault');
        }

        refreshWallet();
    } catch (error) {
        stopPolling();

        if (error instanceof DOMException && error.name === 'AbortError') {
            try {
                const latest = await fetchGeneration(generationId);
                outputText.value = latest.output_text ?? outputText.value;

                if (latest.status === 'stopped') {
                    phase.value = 'failed';
                    errorMessage.value = latest.failure_reason ?? t('generator.stopped');
                }
            } catch {
                phase.value = 'idle';
            }

            refreshWallet();

            return;
        }

        phase.value = 'failed';
        errorMessage.value =
            error instanceof GenerationApiError
                ? error.message
                : t('generator.failedDefault');
        refreshWallet();
    } finally {
        abortController.value = null;
    }
}

async function onGenerate(): Promise<void> {
    formErrors.value = [];

    if (! hasCredits.value) {
        phase.value = 'no_credits';

        return;
    }

    if (brief.value.trim() === '') {
        formErrors.value = [t('generator.topicRequired')];

        return;
    }

    if (
        (toneKind.value === 'built_in' && builtInToneId.value === null)
        || (toneKind.value === 'custom' && customToneId.value === null)
    ) {
        formErrors.value = [t('generator.toneRequired')];

        return;
    }

    try {
        const created = await createGeneration(buildPayload());
        await runStream(created.stream_url, created.id);
    } catch (error) {
        if (error instanceof GenerationApiError) {
            if (error.code === 'insufficient_credits') {
                phase.value = 'no_credits';

                return;
            }

            if (error.code === 'concurrency_limit') {
                errorMessage.value = t('generator.concurrency');
                phase.value = 'failed';

                return;
            }

            formErrors.value = [error.message];
        }
    }
}

async function onStop(): Promise<void> {
    if (activeGenerationId.value === null) {
        return;
    }

    abortController.value?.abort();
    await cancelGeneration(activeGenerationId.value);
}

async function onRegenerate(): Promise<void> {
    if (activeGenerationId.value === null || ! hasCredits.value) {
        if (! hasCredits.value) {
            phase.value = 'no_credits';
        }

        return;
    }

    try {
        const next = await regenerateGeneration(activeGenerationId.value);
        await runStream(next.stream_url, next.id);
    } catch (error) {
        if (error instanceof GenerationApiError && error.code === 'insufficient_credits') {
            phase.value = 'no_credits';
        }
    }
}

async function copyOutput(): Promise<void> {
    if (outputText.value) {
        await navigator.clipboard.writeText(outputText.value);
    }
}

onUnmounted(() => {
    stopPolling();
    abortController.value?.abort();
});
</script>

<template>
    <div class="nova-gen">
        <header class="nova-gen-header">
            <h2 class="nova-gen-page-title">{{ t('generator.pageTitle') }}</h2>
            <p class="nova-muted" style="margin-top: 8px">{{ t('generator.subtitle') }}</p>
        </header>

        <div
            v-if="phase === 'no_credits' || (wallet && wallet.available < 1)"
            class="nova-alert"
        >
            {{ t('generator.noCredits') }}
            <Link href="/billing">{{ t('dashboard.buyCredits') }}</Link>
        </div>

        <FormErrors v-if="formErrors.length" :errors="formErrors" />

        <div class="nova-grid nova-gen-grid">
            <div class="nova-card nova-gen-panel">
                <form class="nova-gen-form" @submit.prevent="onGenerate">
                    <div class="nova-gen-form-fields">
                        <div class="nova-gen-field">
                            <label class="nova-gen-label" for="content-type">
                                {{ t('generator.contentType') }}
                            </label>
                            <select
                                id="content-type"
                                v-model="contentType"
                                class="form-select"
                                :disabled="phase === 'generating'"
                            >
                                <option v-for="type in contentTypes" :key="type" :value="type">
                                    {{ t(`generator.types.${type}`) }} ({{ t('generator.creditBadge') }})
                                </option>
                            </select>
                        </div>

                        <div class="nova-gen-field">
                            <label class="nova-gen-label" for="content-lang">
                                {{ t('generator.contentLanguage') }}
                            </label>
                            <select
                                id="content-lang"
                                v-model="contentLanguage"
                                class="form-select"
                                :disabled="phase === 'generating'"
                            >
                                <option v-for="lang in contentLanguages" :key="lang" :value="lang">
                                    {{ t(`generator.languages.${lang}`) }}
                                </option>
                            </select>
                        </div>

                        <div class="nova-gen-field">
                            <span class="nova-gen-label">{{ t('generator.length') }}</span>
                            <div
                                class="nova-seg nova-gen-seg"
                                role="group"
                                :aria-label="t('generator.length')"
                            >
                                <button
                                    v-for="len in contentLengths"
                                    :key="len"
                                    type="button"
                                    :aria-pressed="contentLength === len"
                                    :disabled="phase === 'generating'"
                                    @click="contentLength = len"
                                >
                                    {{ t(`generator.lengths.${len}`) }}
                                </button>
                            </div>
                            <p class="nova-gen-length-hint">{{ lengthHint }}</p>
                        </div>

                        <div class="nova-gen-field">
                            <label class="nova-gen-label" for="tone-select">
                                {{ t('generator.toneOptional') }}
                            </label>
                            <select
                                id="tone-select"
                                v-model="selectedToneKey"
                                class="form-select"
                                :disabled="phase === 'generating'"
                            >
                                <optgroup v-if="builtInTones.length" :label="t('tones.builtIn')">
                                    <option
                                        v-for="tone in builtInTones"
                                        :key="`b-${tone.id}`"
                                        :value="toneKey('built_in', tone.id)"
                                    >
                                        {{ tone.name }}
                                    </option>
                                </optgroup>
                                <optgroup v-if="customTones.length" :label="t('tones.custom')">
                                    <option
                                        v-for="tone in customTones"
                                        :key="`c-${tone.id}`"
                                        :value="toneKey('custom', tone.id)"
                                    >
                                        {{ tone.name }}
                                    </option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="nova-gen-field nova-gen-field-brief">
                            <div class="nova-gen-label-row">
                                <label class="nova-gen-label" for="brief">{{ t('generator.brief') }}</label>
                                <span class="nova-gen-counter">
                                    {{ brief.length }} / {{ briefMaxLength }}
                                </span>
                            </div>
                            <textarea
                                id="brief"
                                v-model="brief"
                                class="form-control nova-gen-brief"
                                rows="5"
                                :maxlength="briefMaxLength"
                                :placeholder="t('generator.briefPlaceholder')"
                                :disabled="phase === 'generating'"
                            />
                        </div>
                    </div>

                    <div class="nova-gen-actions">
                        <button
                            type="submit"
                            class="nova-btn nova-btn-primary nova-gen-generate"
                            :disabled="!canGenerate"
                        >
                            <span class="nova-gen-generate-main">
                                <span class="nova-gen-wand" aria-hidden="true">✨</span>
                                <span>{{
                                    phase === 'generating'
                                        ? t('generator.generating')
                                        : t('generator.generate')
                                }}</span>
                            </span>
                            <span
                                v-if="phase !== 'generating'"
                                class="nova-badge nova-gen-credit-badge"
                            >
                                {{ t('generator.creditBadge') }}
                            </span>
                        </button>
                        <button
                            v-if="phase === 'generating'"
                            type="button"
                            class="nova-btn-link nova-gen-stop-link"
                            @click="onStop"
                        >
                            {{ t('generator.stop') }}
                        </button>
                    </div>
                </form>
            </div>

            <div class="nova-card nova-gen-panel nova-gen-result">
                <div class="nova-gen-result-head nova-row">
                    <h3>{{ t('generator.outputTitle') }}</h3>
                    <span v-if="phase === 'generating'" class="nova-badge">
                        {{ t('generator.streaming') }}
                    </span>
                </div>

                <p v-if="phase === 'failed'" class="nova-alert" style="margin-bottom: 10px">
                    {{ errorMessage || t('generator.failedDefault') }}
                    <span class="nova-muted"> {{ t('generator.noCharge') }}</span>
                </p>

                <div class="nova-gen-output-wrap">
                    <div
                        class="nova-gen-output-body"
                        :class="{ 'is-empty': outputEmpty && phase !== 'generating' }"
                    >
                        <template v-if="outputText">{{ outputText }}</template>
                        <template v-else-if="phase === 'generating'">
                            {{ t('generator.streaming') }}
                        </template>
                    </div>
                </div>

                <div class="nova-gen-icon-bar">
                    <button
                        type="button"
                        class="nova-gen-icon-btn"
                        :title="t('generator.copy')"
                        :disabled="!outputText"
                        @click="copyOutput"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        class="nova-gen-icon-btn"
                        :title="t('generator.regenerate')"
                        :disabled="!outputText || !hasCredits || phase === 'generating'"
                        @click="onRegenerate"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M3 12a9 9 0 0 1 15-6.7L21 3v6h-6" />
                            <path d="M21 12a9 9 0 0 1-15 6.7L3 21v-6h6" />
                        </svg>
                    </button>
                    <Link
                        v-if="activeGenerationId && phase !== 'generating'"
                        class="nova-gen-icon-btn"
                        :href="`/history/${activeGenerationId}`"
                        :title="t('generator.viewInHistory')"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="M18 13v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                            <path d="M15 3h6v6" />
                            <path d="M10 14 21 3" />
                        </svg>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
