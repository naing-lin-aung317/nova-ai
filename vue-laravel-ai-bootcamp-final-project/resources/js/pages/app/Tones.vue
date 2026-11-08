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
        : t('pages.tones'),
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
    <div>
        <div class="nova-row">
            <h2>{{ t('pages.tones') }}</h2>
            <Link class="nova-btn nova-btn-primary" href="/tones">
                {{ t('tones.newTone') }}
            </Link>
        </div>

        <div class="nova-grid nova-grid-2" style="margin-top: 16px; align-items: start">
            <div class="nova-card">
                <h3>{{ formTitle }}</h3>

                <Form
                    v-if="isEditing"
                    :action="`/tones/${editing!.id}`"
                    method="put"
                    #default="{ errors, processing }"
                >
                    <label class="form-label" for="tone-name">{{ t('tones.name') }}</label>
                    <input
                        id="tone-name"
                        class="form-control"
                        name="name"
                        required
                        type="text"
                        :value="editing!.name"
                    />
                    <FormErrors :errors="errors.name" />

                    <label class="form-label" for="tone-description" style="margin-top: 12px">
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

                    <label class="form-label" for="tone-sample" style="margin-top: 12px">
                        {{ t('tones.sampleContent') }}
                    </label>
                    <textarea
                        id="tone-sample"
                        class="form-control"
                        maxlength="1500"
                        name="sample_content"
                        :placeholder="t('tones.samplePlaceholder')"
                        rows="6"
                        :value="editing!.sample_content ?? ''"
                        @input="onSampleInput"
                    />
                    <p class="nova-muted" style="text-align: right">
                        {{ t('tones.sampleCounter', { count: sampleLength, max: sampleMax }) }}
                    </p>
                    <FormErrors :errors="errors.sample_content" />

                    <div class="nova-row" style="margin-top: 14px">
                        <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                            {{ t('tones.updateTone') }}
                        </button>
                        <Link class="nova-btn" href="/tones">{{ t('tones.cancel') }}</Link>
                    </div>
                </Form>

                <Form
                    v-else
                    action="/tones"
                    method="post"
                    #default="{ errors, processing }"
                >
                    <label class="form-label" for="tone-name">{{ t('tones.name') }}</label>
                    <input id="tone-name" class="form-control" name="name" required type="text" />
                    <FormErrors :errors="errors.name" />

                    <label class="form-label" for="tone-description" style="margin-top: 12px">
                        {{ t('tones.description') }}
                    </label>
                    <input id="tone-description" class="form-control" name="description" type="text" />
                    <FormErrors :errors="errors.description" />

                    <label class="form-label" for="tone-sample" style="margin-top: 12px">
                        {{ t('tones.sampleContent') }}
                    </label>
                    <textarea
                        id="tone-sample"
                        class="form-control"
                        maxlength="1500"
                        name="sample_content"
                        :placeholder="t('tones.samplePlaceholder')"
                        rows="6"
                        @input="onSampleInput"
                    />
                    <p class="nova-muted" style="text-align: right">
                        {{ t('tones.sampleCounter', { count: sampleLength, max: sampleMax }) }}
                    </p>
                    <FormErrors :errors="errors.sample_content" />

                    <button
                        class="nova-btn nova-btn-primary"
                        :disabled="processing"
                        style="margin-top: 14px"
                        type="submit"
                    >
                        {{ t('tones.createTone') }}
                    </button>
                </Form>
            </div>

            <div>
                <div v-for="tone in builtInTones" :key="`built-in-${tone.id}`" class="nova-card">
                    <div class="nova-row">
                        <h3>{{ tone.name }}</h3>
                        <span class="nova-badge">{{ t('tones.builtIn') }}</span>
                    </div>
                    <span class="nova-muted">{{ tone.description }}</span>
                </div>

                <div v-for="tone in customTones" :key="tone.id" class="nova-card">
                    <div class="nova-row">
                        <h3>{{ tone.name }}</h3>
                        <span class="nova-badge" style="border-color: var(--nova-ok)">
                            {{ t('tones.custom') }}
                        </span>
                    </div>
                    <span class="nova-muted">{{ tone.description }}</span>
                    <div class="nova-row" style="margin-top: 8px">
                        <Link class="nova-btn" :href="`/tones?edit=${tone.id}`">
                            {{ t('tones.edit') }}
                        </Link>
                        <Form
                            :action="`/tones/${tone.id}`"
                            method="delete"
                            #default="{ processing }"
                            @submit="confirmDelete"
                        >
                            <button class="nova-btn" :disabled="processing" type="submit">
                                {{ t('tones.delete') }}
                            </button>
                        </Form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
