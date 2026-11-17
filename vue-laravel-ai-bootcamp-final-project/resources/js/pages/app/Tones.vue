<script setup lang="ts">
import { Form, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type ToneCard = {
    id: number;
    name: string;
    description: string | null;
    sample_content?: string | null;
};

type EditingTone = {
    id: number;
    name: string;
    description: string | null;
    sample_content: string | null;
};

type ToneFilter = 'all' | 'built_in' | 'custom';

type ToneListItem = {
    id: number;
    name: string;
    description: string | null;
    kind: 'built_in' | 'custom';
    samplePreview: string | null;
    generatorHref: string;
    editHref?: string;
};

const props = defineProps<{
    builtInTones: ToneCard[];
    customTones: ToneCard[];
    editing: EditingTone | null;
    creating: boolean;
}>();

const { t } = useI18n();
const page = usePage();
const sampleMax = 1500;

const FILTER_STORAGE_KEY = 'nova-tones-active-filter';

function readStoredFilter(): ToneFilter {
    if (typeof sessionStorage === 'undefined') {
        return 'all';
    }

    const stored = sessionStorage.getItem(FILTER_STORAGE_KEY);

    if (stored === 'all' || stored === 'built_in' || stored === 'custom') {
        return stored;
    }

    return 'all';
}

const sampleLength = ref(props.editing?.sample_content?.length ?? 0);
const activeFilter = ref<ToneFilter>(readStoredFilter());
const searchQuery = ref('');

const editForm = ref({
    name: '',
    description: '',
    sample_content: '',
});

watch(activeFilter, (value) => {
    sessionStorage.setItem(FILTER_STORAGE_KEY, value);
});

watch(
    () => props.editing,
    (tone) => {
        if (tone === null) {
            return;
        }

        editForm.value = {
            name: tone.name,
            description: tone.description ?? '',
            sample_content: tone.sample_content ?? '',
        };
        sampleLength.value = editForm.value.sample_content.length;
    },
    { immediate: true },
);

watch(
    () => editForm.value.sample_content,
    (value) => {
        sampleLength.value = value.length;
    },
);

const showForm = computed(() => props.editing !== null || props.creating);

const flashStatus = computed(
    () => (page.props.flash as { status?: string | null } | undefined)?.status ?? undefined,
);

const formTitle = computed(() =>
    props.editing !== null
        ? t('tones.editTone', { name: editForm.value.name || props.editing?.name || '' })
        : t('tones.formCreateTitle'),
);

function hasFormErrors(errors: Record<string, string | string[] | undefined>): boolean {
    return Object.keys(errors).some((key) => {
        const value = errors[key];

        if (value === undefined || value === null) {
            return false;
        }

        if (Array.isArray(value)) {
            return value.length > 0;
        }

        return String(value).length > 0;
    });
}

function onSampleInput(event: Event): void {
    const value = (event.target as HTMLTextAreaElement).value;
    sampleLength.value = value.length;
}

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(t('tones.deleteConfirm'))) {
        event.preventDefault();
    }
}

function truncateSample(text: string, max = 160): string {
    const trimmed = text.trim();

    if (trimmed.length <= max) {
        return trimmed;
    }

    return `${trimmed.slice(0, max - 1).trimEnd()}…`;
}

function builtInSample(name: string): string | null {
    const key = `tones.builtInSamples.${name}`;

    return t(key) !== key ? t(key) : null;
}

function customSamplePreview(sample: string | null | undefined): string | null {
    if (! sample?.trim()) {
        return null;
    }

    return truncateSample(sample);
}

const allToneItems = computed((): ToneListItem[] => {
    const builtIn: ToneListItem[] = props.builtInTones.map((tone) => ({
        id: tone.id,
        name: tone.name,
        description: tone.description,
        kind: 'built_in',
        samplePreview: builtInSample(tone.name),
        generatorHref: `/generator?built_in_tone_id=${tone.id}`,
    }));

    const custom: ToneListItem[] = props.customTones.map((tone) => ({
        id: tone.id,
        name: tone.name,
        description: tone.description,
        kind: 'custom',
        samplePreview: customSamplePreview(tone.sample_content),
        generatorHref: `/generator?custom_tone_id=${tone.id}`,
        editHref: `/tones?edit=${tone.id}`,
    }));

    return [...builtIn, ...custom];
});

const filteredTones = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return allToneItems.value.filter((tone) => {
        if (activeFilter.value === 'built_in' && tone.kind !== 'built_in') {
            return false;
        }

        if (activeFilter.value === 'custom' && tone.kind !== 'custom') {
            return false;
        }

        if (! query) {
            return true;
        }

        const haystack = `${tone.name} ${tone.description ?? ''}`.toLowerCase();

        return haystack.includes(query);
    });
});

const showCreateCard = computed(
    () => activeFilter.value !== 'built_in' && ! showForm.value,
);

const openMenuKey = ref<string | null>(null);

function toneMenuKey(tone: ToneListItem): string {
    return `${tone.kind}-${tone.id}`;
}

function toggleToneMenu(tone: ToneListItem): void {
    const key = toneMenuKey(tone);
    openMenuKey.value = openMenuKey.value === key ? null : key;
}

function closeToneMenu(): void {
    openMenuKey.value = null;
}

function deleteCustomTone(toneId: number): void {
    if (! window.confirm(t('tones.deleteConfirm'))) {
        return;
    }

    closeToneMenu();

    router.delete(`/tones/${toneId}`, {
        preserveScroll: true,
        onError: () => {
            window.alert(t('tones.deleteFailed'));
        },
    });
}

function onDocumentClick(event: MouseEvent): void {
    const target = event.target as HTMLElement | null;

    if (target?.closest('.nova-tones-card-menu')) {
        return;
    }

    closeToneMenu();
}

onMounted(() => {
    document.addEventListener('click', onDocumentClick);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onDocumentClick);
});
</script>

<template>
    <div class="nova-tones">
        <header class="nova-tones-header">
            <div class="nova-tones-head-copy">
                <h1 class="nova-tones-title">{{ t('tones.pageTitle') }}</h1>
                <p class="nova-tones-subtitle">{{ t('tones.pageSubtitle') }}</p>
            </div>
            <Link
                v-if="!showForm"
                class="nova-btn nova-btn-primary nova-tones-new-btn"
                href="/tones?new=1"
            >
                {{ t('tones.newTone') }}
            </Link>
        </header>

        <p v-if="flashStatus && ! showForm" class="nova-alert nova-tones-flash">
            {{ t(flashStatus) }}
        </p>

        <section v-if="showForm" class="nova-card nova-tones-form-card">
            <div class="nova-tones-form-head">
                <h2 class="nova-tones-form-title">{{ formTitle }}</h2>
                <Link class="nova-btn nova-tones-form-close" href="/tones">{{ t('tones.cancel') }}</Link>
            </div>

            <template v-if="editing">
                <Form
                    :action="`/tones/${editing.id}`"
                    method="put"
                    :reset-on-error="false"
                    :options="{ preserveScroll: true }"
                    #default="{ errors, processing }"
                >
                    <p v-if="hasFormErrors(errors)" class="nova-alert" role="alert">
                        {{ t('tones.updateFailed') }}
                    </p>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-name">{{ t('tones.name') }}</label>
                        <input
                            id="tone-name"
                            v-model="editForm.name"
                            class="form-control"
                            name="name"
                            required
                            type="text"
                        />
                        <FormErrors :errors="errors.name" />
                    </div>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label" for="tone-description">
                            {{ t('tones.description') }}
                        </label>
                        <input
                            id="tone-description"
                            v-model="editForm.description"
                            class="form-control"
                            name="description"
                            type="text"
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
                            v-model="editForm.sample_content"
                            class="form-control nova-gen-brief"
                            maxlength="1500"
                            name="sample_content"
                            :placeholder="t('tones.samplePlaceholder')"
                            rows="6"
                        />
                        <FormErrors :errors="errors.sample_content" />
                    </div>

                    <div class="nova-tones-form-actions">
                        <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                            {{ processing ? t('tones.saving') : t('tones.updateTone') }}
                        </button>
                    </div>
                </Form>

                <Form
                    :action="`/tones/${editing.id}`"
                    method="delete"
                    class="nova-tones-form-actions"
                    #default="{ processing: deleting }"
                    @submit="confirmDelete"
                >
                    <button
                        class="nova-btn nova-tones-delete-btn"
                        :disabled="deleting"
                        type="submit"
                    >
                        {{ t('tones.delete') }}
                    </button>
                </Form>
            </template>

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
                        rows="6"
                        @input="onSampleInput"
                    />
                    <FormErrors :errors="errors.sample_content" />
                </div>

                <div class="nova-tones-form-actions">
                    <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                        {{ t('tones.createTone') }}
                    </button>
                </div>
            </Form>
        </section>

        <div class="nova-tones-toolbar">
            <div
                class="nova-seg nova-tones-filter"
                role="tablist"
                :aria-label="t('tones.pageTitle')"
            >
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeFilter === 'all'"
                    :class="{ 'is-active': activeFilter === 'all' }"
                    @click="activeFilter = 'all'"
                >
                    {{ t('tones.filterAll') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeFilter === 'built_in'"
                    :class="{ 'is-active': activeFilter === 'built_in' }"
                    @click="activeFilter = 'built_in'"
                >
                    {{ t('tones.filterBuiltIn') }}
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeFilter === 'custom'"
                    :class="{ 'is-active': activeFilter === 'custom' }"
                    @click="activeFilter = 'custom'"
                >
                    {{ t('tones.filterMyTones') }}
                </button>
            </div>
            <label class="nova-tones-search">
                <span class="sr-only">{{ t('tones.searchPlaceholder') }}</span>
                <input
                    v-model="searchQuery"
                    class="form-control nova-tones-search-input"
                    type="search"
                    :placeholder="t('tones.searchPlaceholder')"
                />
            </label>
        </div>

        <p v-if="filteredTones.length === 0 && !showCreateCard" class="nova-tones-empty">
            {{ t('tones.noSearchResults') }}
        </p>

        <div class="nova-tones-grid">
            <Link
                v-if="showCreateCard"
                class="nova-tones-create-card"
                href="/tones?new=1"
            >
                <span class="nova-tones-create-icon" aria-hidden="true">+</span>
                <span class="nova-tones-create-title">{{ t('tones.createCardTitle') }}</span>
                <span class="nova-tones-create-desc">{{ t('tones.createCardDesc') }}</span>
            </Link>

            <article
                v-for="tone in filteredTones"
                :key="`${tone.kind}-${tone.id}`"
                class="nova-card nova-tones-card"
            >
                <div class="nova-tones-card-head">
                    <h2 class="nova-tones-card-name">
                        <span>{{ tone.name }}</span>
                    </h2>
                    <div class="nova-tones-card-head-end">
                        <span
                            class="nova-tones-badge"
                            :class="tone.kind === 'built_in' ? 'is-built-in' : 'is-custom'"
                        >
                            {{ tone.kind === 'built_in' ? t('tones.builtIn') : t('tones.custom') }}
                        </span>
                        <div
                            v-if="tone.kind === 'custom'"
                            class="nova-tones-card-menu"
                        >
                            <button
                                type="button"
                                class="nova-tones-card-menu-trigger"
                                :aria-expanded="openMenuKey === toneMenuKey(tone)"
                                :aria-haspopup="true"
                                :aria-label="t('tones.toneMenu')"
                                @click.stop="toggleToneMenu(tone)"
                            >
                                <span aria-hidden="true">⋯</span>
                            </button>
                            <div
                                v-if="openMenuKey === toneMenuKey(tone)"
                                class="nova-tones-card-menu-panel"
                                role="menu"
                            >
                                <Link
                                    v-if="tone.editHref"
                                    class="nova-tones-card-menu-item"
                                    role="menuitem"
                                    :href="tone.editHref"
                                    @click="closeToneMenu"
                                >
                                    {{ t('tones.editToneAction') }}
                                </Link>
                                <button
                                    type="button"
                                    class="nova-tones-card-menu-item is-danger"
                                    role="menuitem"
                                    @click="deleteCustomTone(tone.id)"
                                >
                                    {{ t('tones.deleteToneAction') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <p v-if="tone.description" class="nova-tones-card-desc">{{ tone.description }}</p>
                <blockquote v-if="tone.samplePreview" class="nova-tones-sample">
                    “{{ tone.samplePreview }}”
                </blockquote>
                <Link class="nova-btn nova-tones-use-btn" :href="tone.generatorHref">
                    {{ t('tones.useInGenerator') }}
                </Link>
            </article>
        </div>
    </div>
</template>
