<script setup lang="ts">
import { Form, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import {
    GenerationApiError,
    regenerateGeneration,
    streamGeneration,
} from '@/lib/generationStream';
import type { GenerationDetail } from '@/types/generation';

defineOptions({ layout: AppLayout });

const props = defineProps<{
    generation: GenerationDetail;
}>();

const { t } = useI18n();

const outputDraft = ref(props.generation.output_text ?? '');
const streaming = ref(false);
const streamPreview = ref('');
const errorMessage = ref('');

const displayOutput = computed(() => {
    if (streaming.value) {
        return streamPreview.value;
    }

    if (props.generation.can_edit) {
        return outputDraft.value;
    }

    return props.generation.output_text ?? '';
});

function formatDate(iso: string | null): string {
    if (! iso) {
        return '—';
    }

    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(t('history.deleteConfirm'))) {
        event.preventDefault();
    }
}

async function copyOutput(): Promise<void> {
    const text = displayOutput.value;

    if (text) {
        await navigator.clipboard.writeText(text);
    }
}

async function onRegenerate(): Promise<void> {
    errorMessage.value = '';
    streaming.value = true;
    streamPreview.value = '';

    try {
        const next = await regenerateGeneration(props.generation.id);
        await streamGeneration(next.stream_url, (chunk) => {
            streamPreview.value += chunk;
        });

        streaming.value = false;
        router.visit(`/history/${next.id}`, { replace: true });
        router.reload({ only: ['wallet'] });
    } catch (error) {
        streaming.value = false;

        if (error instanceof GenerationApiError) {
            errorMessage.value = error.message;
        }
    }
}
</script>

<template>
    <div class="nova-hist-show">
        <Link class="nova-hist-back" href="/history">
            ← {{ t('history.backToList') }}
        </Link>

        <p v-if="generation.failure_reason" class="nova-alert" style="margin-top: 12px">
            {{ generation.failure_reason }}
        </p>

        <p v-if="errorMessage" class="nova-alert" style="margin-top: 12px">{{ errorMessage }}</p>

        <div class="nova-card nova-hist-detail-card">
            <div class="nova-hist-detail-head">
                <h2 class="nova-hist-detail-title">{{ t('history.generatedContent') }}</h2>
                <div class="nova-hist-detail-toolbar">
                    <button
                        type="button"
                        class="nova-btn nova-hist-toolbar-btn"
                        :disabled="!displayOutput"
                        @click="copyOutput"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <rect x="9" y="9" width="13" height="13" rx="2" />
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
                        </svg>
                        {{ t('generator.copy') }}
                    </button>
                    <Form
                        v-if="generation.can_delete"
                        :action="`/generations/${generation.id}`"
                        method="delete"
                        #default="{ processing }"
                        @submit="confirmDelete"
                    >
                        <button
                            type="submit"
                            class="nova-btn nova-hist-toolbar-btn is-danger"
                            :disabled="processing"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path d="M3 6h18" />
                                <path d="M8 6V4h8v2" />
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                <path d="M10 11v6M14 11v6" />
                            </svg>
                            {{ t('history.delete') }}
                        </button>
                    </Form>
                </div>
            </div>

            <div class="nova-gen-output-wrap nova-hist-output-wrap">
                <Form
                    v-if="generation.can_edit && !streaming"
                    :action="`/generations/${generation.id}`"
                    method="patch"
                    class="nova-hist-output-form"
                    #default="{ errors, processing }"
                >
                    <textarea
                        v-model="outputDraft"
                        name="output_text"
                        class="nova-hist-output-edit"
                        rows="16"
                    />
                    <p v-if="errors.output_text" class="nova-hist-output-error">
                        {{ errors.output_text }}
                    </p>
                    <div class="nova-hist-output-foot">
                        <p class="nova-muted">{{ t('generator.doneNote') }}</p>
                        <button type="submit" class="nova-btn nova-btn-primary" :disabled="processing">
                            {{ t('history.saveOutput') }}
                        </button>
                    </div>
                </Form>

                <template v-else>
                    <div
                        class="nova-gen-output-body nova-hist-output-body"
                        :class="{ 'is-empty': !displayOutput && !streaming }"
                    >
                        <template v-if="displayOutput">{{ displayOutput }}</template>
                        <template v-else-if="streaming">{{ t('generator.streaming') }}</template>
                    </div>
                </template>
            </div>

            <div v-if="streaming" class="nova-muted" style="margin-top: 10px; font-size: 13px">
                {{ t('generator.streaming') }}
            </div>
        </div>

        <div class="nova-card nova-hist-meta-card">
            <h3 class="nova-hist-meta-title">{{ t('history.detailsTitle') }}</h3>
            <dl class="nova-hist-meta-grid">
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailTopic') }}</dt>
                    <dd>{{ generation.topic }}</dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailType') }}</dt>
                    <dd>{{ t(`generator.types.${generation.content_type}`) }}</dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailLanguage') }}</dt>
                    <dd>{{ t(`generator.languages.${generation.language}`) }}</dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailLength') }}</dt>
                    <dd>{{ t(`generator.lengths.${generation.length}`) }}</dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailTone') }}</dt>
                    <dd>{{ generation.tone_label ?? '—' }}</dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailStatus') }}</dt>
                    <dd>
                        {{ t(`history.statuses.${generation.status}`) }}
                        <span v-if="generation.charged" class="nova-muted"> · {{ t('history.charged') }}</span>
                        <span v-else-if="generation.no_charge" class="nova-muted"> · {{ t('history.noCharge') }}</span>
                    </dd>
                </div>
                <div class="nova-hist-meta-row">
                    <dt>{{ t('history.detailDate') }}</dt>
                    <dd>{{ formatDate(generation.created_at) }}</dd>
                </div>
            </dl>

            <button
                v-if="!streaming"
                type="button"
                class="nova-btn nova-btn-primary"
                style="margin-top: 14px"
                @click="onRegenerate"
            >
                {{ t('generator.regenerate') }}
            </button>
        </div>
    </div>
</template>
