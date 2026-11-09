<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type ToneCard = {
    id: number;
    name: string;
    description: string | null;
};

type EditingTone = {
    id: number;
    name: string;
    description: string | null;
    sample_content: string | null;
};

const props = defineProps<{
    builtInTones: ToneCard[];
    customTones: ToneCard[];
    editing: EditingTone | null;
}>();

const { t } = useI18n();
const sampleMax = 1500;

const sampleLength = ref(props.editing?.sample_content?.length ?? 0);

watch(
    () => props.editing,
    (tone) => {
        sampleLength.value = tone?.sample_content?.length ?? 0;
    },
);

const isEditing = computed(() => props.editing !== null);

const formTitle = computed(() =>
    isEditing.value
        ? t('tones.editTone', { name: props.editing?.name ?? '' })
        : t('tones.formCreateTitle'),
);

function onSampleInput(event: Event): void {
    const value = (event.target as HTMLTextAreaElement).value;
    sampleLength.value = value.length;
}

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(t('tones.deleteConfirm'))) {
        event.preventDefault();
    }
}
</script>

<template>
    <div class="nova-tones">
        <header class="nova-tones-header">
            <div>
                <h2 class="nova-tones-title">{{ t('tones.pageTitle') }}</h2>
                <p class="nova-muted" style="margin-top: 8px">{{ t('tones.pageSubtitle') }}</p>
            </div>
            <Link v-if="isEditing" class="nova-btn nova-btn-primary" href="/tones">
                {{ t('tones.newTone') }}
            </Link>
        </header>

        <div class="nova-tones-split">
            <section class="nova-card nova-tones-form-card">
                <h3 class="nova-tones-section-title">{{ formTitle }}</h3>

                <Form
                    v-if="isEditing"
                    :action="`/tones/${editing!.id}`"
                    method="put"
                    #default="{ errors, processing }"
                >
                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-name">{{ t('tones.name') }}</label>
                        <input
                            id="tone-name"
                            class="form-control"
                            name="name"
                            required
                            type="text"
                            :value="editing!.name"
                        />
                        <FormErrors :errors="errors.name" />
                    </div>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-description">
                            {{ t('tones.description') }}
                        </label>
                        <input
                            id="tone-description"
                            class="form-control"
                            name="description"
                            type="text"
                            :value="editing!.description ?? ''"
                        />
                        <FormErrors :errors="errors.description" />
                    </div>

                    <div class="nova-settings-field">
                        <div class="nova-gen-label-row">
                            <label class="nova-settings-label" for="tone-sample">
                                {{ t('tones.sampleContent') }}
                            </label>
                            <span class="nova-gen-counter">
                                {{ t('tones.sampleCounter', { count: sampleLength, max: sampleMax }) }}
                            </span>
                        </div>
                        <textarea
                            id="tone-sample"
                            class="form-control nova-gen-brief"
                            maxlength="1500"
                            name="sample_content"
                            :placeholder="t('tones.samplePlaceholder')"
                            rows="7"
                            :value="editing!.sample_content ?? ''"
                            @input="onSampleInput"
                        />
                        <FormErrors :errors="errors.sample_content" />
                    </div>

                    <div class="nova-settings-actions">
                        <div class="nova-row" style="gap: 8px; flex-wrap: wrap">
                            <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                                {{ t('tones.updateTone') }}
                            </button>
                            <Link class="nova-btn" href="/tones">{{ t('tones.cancel') }}</Link>
                        </div>
                    </div>
                </Form>

                <Form v-else action="/tones" method="post" #default="{ errors, processing }">
                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-name-new">{{ t('tones.name') }}</label>
                        <input id="tone-name-new" class="form-control" name="name" required type="text" />
                        <FormErrors :errors="errors.name" />
                    </div>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-description-new">
                            {{ t('tones.description') }}
                        </label>
                        <input id="tone-description-new" class="form-control" name="description" type="text" />
                        <FormErrors :errors="errors.description" />
                    </div>

                    <div class="nova-settings-field">
                        <div class="nova-gen-label-row">
                            <label class="nova-settings-label" for="tone-sample-new">
                                {{ t('tones.sampleContent') }}
                            </label>
                            <span class="nova-gen-counter">
                                {{ t('tones.sampleCounter', { count: sampleLength, max: sampleMax }) }}
                            </span>
                        </div>
                        <textarea
                            id="tone-sample-new"
                            class="form-control nova-gen-brief"
                            maxlength="1500"
                            name="sample_content"
                            :placeholder="t('tones.samplePlaceholder')"
                            rows="7"
                            @input="onSampleInput"
                        />
                        <FormErrors :errors="errors.sample_content" />
                    </div>

                    <div class="nova-settings-actions">
                        <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                            {{ t('tones.createTone') }}
                        </button>
                    </div>
                </Form>
            </section>

            <div class="nova-tones-library">
                <section class="nova-card nova-tones-list-card">
                    <h3 class="nova-tones-section-title">{{ t('tones.builtInSection') }}</h3>
                    <p class="nova-muted nova-tones-section-hint">{{ t('tones.builtInHint') }}</p>

                    <div
                        v-for="tone in builtInTones"
                        :key="`built-in-${tone.id}`"
                        class="nova-tones-list-row is-built-in"
                    >
                        <div>
                            <p class="nova-tones-list-name">{{ tone.name }}</p>
                            <p v-if="tone.description" class="nova-muted" style="margin: 4px 0 0; font-size: 13px">
                                {{ tone.description }}
                            </p>
                        </div>
                        <span class="nova-badge">{{ t('tones.builtIn') }}</span>
                    </div>
                </section>

                <section class="nova-card nova-tones-list-card">
                    <h3 class="nova-tones-section-title">{{ t('tones.customSection') }}</h3>

                    <p v-if="customTones.length === 0" class="nova-tones-empty">
                        {{ t('tones.emptyCustom') }}
                    </p>

                    <div
                        v-for="tone in customTones"
                        :key="tone.id"
                        class="nova-tones-list-row"
                    >
                        <div class="nova-tones-list-main">
                            <p class="nova-tones-list-name">{{ tone.name }}</p>
                            <p v-if="tone.description" class="nova-muted" style="margin: 4px 0 0; font-size: 13px">
                                {{ tone.description }}
                            </p>
                        </div>
                        <div class="nova-hist-actions">
                            <Link
                                class="nova-hist-action-btn"
                                :href="`/tones?edit=${tone.id}`"
                                :title="t('tones.edit')"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                </svg>
                            </Link>
                            <Form
                                :action="`/tones/${tone.id}`"
                                method="delete"
                                #default="{ processing }"
                                @submit="confirmDelete"
                            >
                                <button
                                    type="submit"
                                    class="nova-hist-action-btn is-delete"
                                    :title="t('tones.delete')"
                                    :disabled="processing"
                                >
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <path d="M3 6h18" />
                                        <path d="M8 6V4h8v2" />
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                        <path d="M10 11v6M14 11v6" />
                                    </svg>
                                </button>
                            </Form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
