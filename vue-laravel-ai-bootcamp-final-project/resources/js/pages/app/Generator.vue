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
import { generationFailureMessage } from '@/utils/generationErrors';

defineOptions({ layout: AppLayout });

type ResumeState = {
    id: number;
    status: string;
    output_text: string | null;
};

type ActiveGenerationState = {
    id: number;
    status: string;
};

type GeneratorPrefill = {
    content_type: string | null;
    topic: string | null;
    built_in_tone_id: number | null;
    custom_tone_id: number | null;
};

const props = defineProps<{
    builtInTones: ToneOption[];
    customTones: ToneOption[];
    contentTypes: string[];
    contentLanguages: string[];
    contentLengths: string[];
    resume: ResumeState | null;
    activeGeneration: ActiveGenerationState | null;
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
const toneKind = ref<'built_in' | 'custom' | 'none'>('built_in');
const builtInToneId = ref<number | null>(null);
const customToneId = ref<number | null>(null);
const brief = ref(props.prefill.topic ?? '');

if (props.prefill.content_type && props.contentTypes.includes(props.prefill.content_type)) {
    contentType.value = props.prefill.content_type;
}

function toneKey(kind: 'built_in' | 'custom', id: number): string {
    return `${kind}:${id}`;
}

function defaultToneKey(): string {
    const friendly = props.builtInTones.find((tone) => tone.name === 'Friendly');

    if (friendly) {
        return toneKey('built_in', friendly.id);
    }

    if (props.builtInTones[0]) {
        return toneKey('built_in', props.builtInTones[0].id);
    }

    if (props.customTones[0]) {
        return toneKey('custom', props.customTones[0].id);
    }

    return 'none';
}

const selectedToneKey = ref(defaultToneKey());

if (
    props.prefill.built_in_tone_id
    && props.builtInTones.some((tone) => tone.id === props.prefill.built_in_tone_id)
) {
    selectedToneKey.value = toneKey('built_in', props.prefill.built_in_tone_id);
}

if (
    props.prefill.custom_tone_id
    && props.customTones.some((tone) => tone.id === props.prefill.custom_tone_id)
) {
    selectedToneKey.value = toneKey('custom', props.prefill.custom_tone_id);
}

watch(
    selectedToneKey,
    (key) => {
        if (key === 'none' || key === '') {
            toneKind.value = 'none';
            builtInToneId.value = null;
            customToneId.value = null;

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
    },
    { immediate: true },
);

const phase = ref<'idle' | 'generating' | 'done' | 'failed' | 'no_credits'>('idle');
const outputText = ref('');
const activeGenerationId = ref<number | null>(null);
const errorMessage = ref('');
const concurrencyBlocked = ref(false);
const blockingGenerationId = ref<number | null>(null);
const cancellingActive = ref(false);
const formErrors = ref<string[]>([]);
const abortController = ref<AbortController | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const hasCredits = computed(() => (wallet.value?.available ?? 0) >= 1);
const canGenerate = computed(
    () => hasCredits.value && phase.value !== 'generating' && brief.value.trim() !== '',
);

const outputEmpty = computed(() => outputText.value.trim() === '');

const wizardStep = ref(1);

const selectedToneName = computed(() => {
    if (toneKind.value === 'none') {
        return t('generator.noTone');
    }

    if (toneKind.value === 'built_in' && builtInToneId.value !== null) {
        return (
            props.builtInTones.find((tone) => tone.id === builtInToneId.value)?.name ?? '—'
        );
    }

    if (toneKind.value === 'custom' && customToneId.value !== null) {
        return (
            props.customTones.find((tone) => tone.id === customToneId.value)?.name ?? '—'
        );
    }

    return t('generator.noTone');
});

const summaryLengthLabel = computed(() => t(`generator.lengths.${contentLength.value}`));

const styleStepSummary = computed(
    () =>
        `${t(`generator.languages.${contentLanguage.value}`)} · ${summaryLengthLabel.value} · ${selectedToneName.value}`,
);

const stepperTypeValue = computed(() =>
    wizardStep.value > 1 ? t(`generator.types.${contentType.value}`) : '',
);

const stepperStyleValue = computed(() => (wizardStep.value > 2 ? styleStepSummary.value : ''));

const stepperBriefValue = computed(() =>
    wizardStep.value === 3 ? t('generator.stepBriefTitle') : '',
);

const resultMetaLine = computed(() =>
    t('generator.resultMeta', {
        type: t(`generator.types.${contentType.value}`),
        language: t(`generator.languages.${contentLanguage.value}`),
        length: summaryLengthLabel.value,
        tone: selectedToneName.value,
    }),
);

const creditsLeftLine = computed(() =>
    t('generator.creditsLeft', { count: wallet.value?.available ?? 0 }),
);

const cancelTargetId = computed((): number | null => {
    if (blockingGenerationId.value !== null) {
        return blockingGenerationId.value;
    }

    if (props.activeGeneration !== null) {
        return props.activeGeneration.id;
    }

    if (phase.value === 'generating' && activeGenerationId.value !== null) {
        return activeGenerationId.value;
    }

    return null;
});

const showActiveGenerationBar = computed(() => cancelTargetId.value !== null);

function customToneDescription(tone: ToneOption): string {
    return tone.description?.trim() || t('generator.customToneDefaultDesc');
}

function goNext(): void {
    if (wizardStep.value < 3) {
        wizardStep.value += 1;
        formErrors.value = [];
    }
}

function goBack(): void {
    if (wizardStep.value > 1 && phase.value !== 'generating') {
        wizardStep.value -= 1;
        formErrors.value = [];
    }
}

function typeDescriptionKey(type: string): string {
    const key = `generator.typeDescriptions.${type}`;

    return t(key) !== key ? key : '';
}

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
            errorMessage.value = generationFailureMessage(latest.failure_reason, t);
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
                    errorMessage.value =
                        latest.status === 'stopped'
                            ? t('generator.stopped')
                            : generationFailureMessage(latest.failure_reason, t);
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
    wizardStep.value = 3;

    if (! hasCredits.value) {
        phase.value = 'no_credits';

        return;
    }

    if (brief.value.trim() === '') {
        formErrors.value = [t('generator.topicRequired')];

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
                concurrencyBlocked.value = true;
                blockingGenerationId.value = error.activeGenerationId ?? null;
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

    await onCancelActiveGeneration();
}

async function onCancelActiveGeneration(): Promise<void> {
    const id = cancelTargetId.value;

    if (id === null || cancellingActive.value) {
        return;
    }

    cancellingActive.value = true;

    try {
        abortController.value?.abort();
        await cancelGeneration(id);

        if (activeGenerationId.value === id) {
            activeGenerationId.value = null;
            outputText.value = '';
        }

        blockingGenerationId.value = null;
        concurrencyBlocked.value = false;
        errorMessage.value = '';
        formErrors.value = [];
        phase.value = 'idle';
        refreshWallet();
        router.reload({ only: ['activeGeneration', 'wallet'] });
    } catch (error) {
        formErrors.value = [
            error instanceof GenerationApiError ? error.message : t('generator.cancelActiveFailed'),
        ];
    } finally {
        cancellingActive.value = false;
    }
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
        if (error instanceof GenerationApiError) {
            if (error.code === 'insufficient_credits') {
                phase.value = 'no_credits';

                return;
            }

            if (error.code === 'concurrency_limit') {
                errorMessage.value = t('generator.concurrency');
                concurrencyBlocked.value = true;
                blockingGenerationId.value = error.activeGenerationId ?? null;
                phase.value = 'failed';
            }
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
            <h1 class="nova-gen-page-title">{{ t('generator.pageTitle') }}</h1>
            <p class="nova-gen-subtitle">{{ t('generator.subtitle') }}</p>
        </header>

        <div
            v-if="phase === 'no_credits' || (wallet && wallet.available < 1)"
            class="nova-alert"
        >
            {{ t('generator.noCredits') }}
            <Link href="/billing">{{ t('dashboard.buyCredits') }}</Link>
        </div>

        <FormErrors v-if="formErrors.length" :errors="formErrors" />

        <div v-if="showActiveGenerationBar" class="nova-gen-active-bar">
            <p class="nova-gen-active-bar-text">
                {{
                    concurrencyBlocked
                        ? t('generator.concurrency')
                        : t('generator.activeGenerationHint')
                }}
            </p>
            <button
                type="button"
                class="nova-btn nova-gen-cancel-active-btn"
                :disabled="cancellingActive"
                @click="onCancelActiveGeneration"
            >
                {{
                    cancellingActive
                        ? t('generator.cancellingActive')
                        : t('generator.cancelActive')
                }}
            </button>
        </div>

        <nav class="nova-gen-stepper" aria-label="Generation steps">
            <ol class="nova-gen-stepper-list">
                <li
                    class="nova-gen-step"
                    :class="{
                        'is-active': wizardStep === 1,
                        'is-done': wizardStep > 1,
                    }"
                >
                    <div class="nova-gen-step-head">
                        <span class="nova-gen-step-marker" aria-hidden="true">
                            <svg
                                v-if="wizardStep > 1"
                                class="nova-gen-step-check"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M3.5 8.5 6.5 11.5 12.5 4.5"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                            <template v-else>1</template>
                        </span>
                        <span class="nova-gen-step-label">{{ t('generator.stepType') }}</span>
                    </div>
                    <p v-if="stepperTypeValue" class="nova-gen-step-value">{{ stepperTypeValue }}</p>
                </li>
                <li
                    class="nova-gen-step"
                    :class="{
                        'is-active': wizardStep === 2,
                        'is-done': wizardStep > 2 || phase === 'done',
                    }"
                >
                    <div class="nova-gen-step-head">
                        <span class="nova-gen-step-marker" aria-hidden="true">
                            <svg
                                v-if="wizardStep > 2 || phase === 'done'"
                                class="nova-gen-step-check"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M3.5 8.5 6.5 11.5 12.5 4.5"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                            <template v-else>2</template>
                        </span>
                        <span class="nova-gen-step-label">{{ t('generator.stepStyle') }}</span>
                    </div>
                    <p v-if="stepperStyleValue" class="nova-gen-step-value">{{ stepperStyleValue }}</p>
                </li>
                <li
                    class="nova-gen-step"
                    :class="{
                        'is-active': wizardStep === 3 && phase !== 'done',
                        'is-done': phase === 'done',
                    }"
                >
                    <div class="nova-gen-step-head">
                        <span class="nova-gen-step-marker" aria-hidden="true">
                            <svg
                                v-if="phase === 'done'"
                                class="nova-gen-step-check"
                                viewBox="0 0 16 16"
                                fill="none"
                            >
                                <path
                                    d="M3.5 8.5 6.5 11.5 12.5 4.5"
                                    stroke="currentColor"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                />
                            </svg>
                            <template v-else>3</template>
                        </span>
                        <span class="nova-gen-step-label">{{ t('generator.stepBrief') }}</span>
                    </div>
                    <p v-if="stepperBriefValue" class="nova-gen-step-value">{{ stepperBriefValue }}</p>
                </li>
            </ol>
        </nav>

        <div class="nova-gen-layout">
            <div class="nova-card nova-gen-panel nova-gen-wizard">
                <form class="nova-gen-form" @submit.prevent="onGenerate">
                    <div v-if="wizardStep === 1" class="nova-gen-step-body">
                        <h2 class="nova-gen-step-title">{{ t('generator.stepTypeTitle') }}</h2>
                        <div
                            class="nova-gen-type-list"
                            role="radiogroup"
                            :aria-label="t('generator.contentType')"
                        >
                            <button
                                v-for="type in contentTypes"
                                :key="type"
                                type="button"
                                role="radio"
                                class="nova-gen-type-option"
                                :class="{ 'is-selected': contentType === type }"
                                :aria-checked="contentType === type"
                                :disabled="phase === 'generating'"
                                @click="contentType = type"
                            >
                                <span class="nova-gen-type-radio" aria-hidden="true" />
                                <span class="nova-gen-type-copy">
                                    <span class="nova-gen-type-name">
                                        {{ t(`generator.types.${type}`) }}
                                    </span>
                                    <span
                                        v-if="typeDescriptionKey(type)"
                                        class="nova-gen-type-desc"
                                    >
                                        {{ t(typeDescriptionKey(type)) }}
                                    </span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-else-if="wizardStep === 2" class="nova-gen-step-body">
                        <h2 class="nova-gen-step-title">{{ t('generator.stepStyleTitle') }}</h2>
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

                        <div class="nova-gen-field nova-gen-field-length">
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
                        </div>

                        <div class="nova-gen-field nova-gen-field-tone">
                            <div class="nova-gen-tone-head">
                                <span class="nova-gen-label nova-gen-label-inline">
                                    {{ t('generator.toneOptional') }}
                                </span>
                                <Link class="nova-gen-manage-tones" href="/tones">
                                    {{ t('generator.manageTones') }}
                                </Link>
                            </div>
                            <div
                                class="nova-gen-tone-grid"
                                role="radiogroup"
                                :aria-label="t('generator.toneOptional')"
                            >
                                <button
                                    type="button"
                                    role="radio"
                                    class="nova-gen-tone-option"
                                    :class="{ 'is-selected': selectedToneKey === 'none' }"
                                    :aria-checked="selectedToneKey === 'none'"
                                    :disabled="phase === 'generating'"
                                    @click="selectedToneKey = 'none'"
                                >
                                    <span class="nova-gen-type-radio" aria-hidden="true" />
                                    <span class="nova-gen-type-copy">
                                        <span class="nova-gen-type-name">{{ t('generator.noTone') }}</span>
                                        <span class="nova-gen-type-desc">{{ t('generator.noToneDesc') }}</span>
                                    </span>
                                </button>

                                <button
                                    v-for="tone in builtInTones"
                                    :key="`b-${tone.id}`"
                                    type="button"
                                    role="radio"
                                    class="nova-gen-tone-option"
                                    :class="{
                                        'is-selected': selectedToneKey === toneKey('built_in', tone.id),
                                    }"
                                    :aria-checked="selectedToneKey === toneKey('built_in', tone.id)"
                                    :disabled="phase === 'generating'"
                                    @click="selectedToneKey = toneKey('built_in', tone.id)"
                                >
                                    <span class="nova-gen-type-radio" aria-hidden="true" />
                                    <span class="nova-gen-type-copy">
                                        <span class="nova-gen-type-name">{{ tone.name }}</span>
                                        <span v-if="tone.description" class="nova-gen-type-desc">
                                            {{ tone.description }}
                                        </span>
                                    </span>
                                </button>

                                <p class="nova-gen-tone-section-label">
                                    {{ t('generator.myTonesSection') }}
                                </p>

                                <button
                                    v-for="tone in customTones"
                                    :key="`c-${tone.id}`"
                                    type="button"
                                    role="radio"
                                    class="nova-gen-tone-option"
                                    :class="{
                                        'is-selected': selectedToneKey === toneKey('custom', tone.id),
                                    }"
                                    :aria-checked="selectedToneKey === toneKey('custom', tone.id)"
                                    :disabled="phase === 'generating'"
                                    @click="selectedToneKey = toneKey('custom', tone.id)"
                                >
                                    <span class="nova-gen-type-radio" aria-hidden="true" />
                                    <span class="nova-gen-type-copy">
                                        <span class="nova-gen-type-name">{{ tone.name }}</span>
                                        <span class="nova-gen-type-desc">
                                            {{ customToneDescription(tone) }}
                                        </span>
                                    </span>
                                </button>

                                <Link
                                    class="nova-gen-tone-new"
                                    href="/tones?new=1"
                                >
                                    {{ t('generator.newToneCard') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div v-else class="nova-gen-step-body">
                        <div class="nova-gen-field nova-gen-field-brief">
                            <div class="nova-gen-label-row">
                                <label class="nova-gen-label" for="brief">
                                    {{ t('generator.yourBrief') }}
                                </label>
                                <span class="nova-gen-counter">
                                    {{ brief.length }} / {{ briefMaxLength }}
                                </span>
                            </div>
                            <textarea
                                id="brief"
                                v-model="brief"
                                class="form-control nova-gen-brief"
                                rows="8"
                                :maxlength="briefMaxLength"
                                :placeholder="t('generator.briefPlaceholder')"
                                :disabled="phase === 'generating'"
                            />
                            <p class="nova-gen-brief-tip">{{ t('generator.briefTip') }}</p>
                        </div>
                    </div>

                    <footer class="nova-gen-wizard-footer">
                        <div
                            class="nova-gen-wizard-footer-actions"
                            :class="{ 'is-brief-step': wizardStep === 3 }"
                        >
                            <button
                                v-if="wizardStep > 1 && phase !== 'generating'"
                                type="button"
                                class="nova-btn nova-gen-back-btn"
                                @click="goBack"
                            >
                                {{ t('generator.back') }}
                            </button>
                            <button
                                v-if="wizardStep < 3"
                                type="button"
                                class="nova-btn nova-btn-primary nova-gen-next-btn"
                                :disabled="phase === 'generating'"
                                @click="goNext"
                            >
                                {{ t('generator.next') }}
                            </button>
                            <template v-else>
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
                                                : t('generator.generateWithCredit')
                                        }}</span>
                                    </span>
                                </button>
                            </template>
                        </div>
                        <button
                            v-if="wizardStep === 3 && phase === 'generating'"
                            type="button"
                            class="nova-btn-link nova-gen-stop-link"
                            @click="onStop"
                        >
                            {{ t('generator.stop') }}
                        </button>
                        <p
                            v-if="wizardStep === 3 && wallet"
                            class="nova-gen-credits-foot"
                        >
                            {{ creditsLeftLine }}
                        </p>
                    </footer>
                </form>
            </div>

            <div class="nova-card nova-gen-panel nova-gen-result">
                <div class="nova-gen-result-head">
                    <h3 class="nova-gen-result-title">{{ t('generator.outputTitle') }}</h3>
                    <div class="nova-gen-result-actions">
                        <span v-if="phase === 'generating'" class="nova-badge nova-gen-stream-badge">
                            {{ t('generator.streaming') }}
                        </span>
                        <button
                            type="button"
                            class="nova-btn nova-btn-primary nova-gen-action-copy"
                            :disabled="!outputText"
                            @click="copyOutput"
                        >
                            {{ t('generator.copy') }}
                        </button>
                        <button
                            type="button"
                            class="nova-btn nova-gen-action-regen"
                            :disabled="!outputText || !hasCredits || phase === 'generating'"
                            @click="onRegenerate"
                        >
                            {{ t('generator.regenerate') }}
                        </button>
                    </div>
                </div>

                <div v-if="phase === 'failed'" class="nova-alert nova-gen-result-alert">
                    <p style="margin: 0">
                        {{ errorMessage || t('generator.failedDefault') }}
                        <span v-if="!concurrencyBlocked" class="nova-muted">
                            {{ t('generator.noCharge') }}
                        </span>
                    </p>
                    <button
                        v-if="concurrencyBlocked && cancelTargetId"
                        type="button"
                        class="nova-btn nova-gen-cancel-active-btn nova-gen-result-cancel"
                        :disabled="cancellingActive"
                        @click="onCancelActiveGeneration"
                    >
                        {{ t('generator.cancelActive') }}
                    </button>
                </div>

                <div class="nova-gen-output-wrap">
                    <div
                        class="nova-gen-output-body"
                        :class="{ 'is-empty': outputEmpty && phase !== 'generating' }"
                    >
                        <template v-if="outputText">{{ outputText }}</template>
                        <template v-else-if="phase === 'generating'">
                            {{ t('generator.streaming') }}
                        </template>
                        <template v-else>
                            {{ t('generator.resultPlaceholderSub') }}
                        </template>
                    </div>
                </div>

                <p v-if="phase === 'done'" class="nova-gen-result-meta">
                    {{ resultMetaLine }}
                </p>
                <Link
                    v-else-if="activeGenerationId && outputText"
                    class="nova-gen-history-link"
                    :href="`/history/${activeGenerationId}`"
                >
                    {{ t('generator.viewInHistory') }}
                </Link>
            </div>
        </div>
    </div>
</template>
