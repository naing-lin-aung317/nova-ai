<script setup lang="ts">
import { Form, Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

type Tone = {
    id: number;
    name: string;
    description: string;
    is_active: boolean;
    sort_order: number;
};

const props = defineProps<{
    tones: Tone[];
    summary: { total: number; active: number };
}>();

const { t } = useI18n();

const editingToneId = ref<number | null>(null);

const nextSortOrder = computed(() => {
    if (props.tones.length === 0) {
        return 1;
    }

    return Math.max(...props.tones.map((tone) => tone.sort_order)) + 1;
});

const createForm = useForm({
    name: '',
    description: '',
    is_active: true,
    sort_order: nextSortOrder.value,
});

const editForm = useForm({
    name: '',
    description: '',
    is_active: true as boolean,
    sort_order: 0,
});

const editingTone = computed(() => props.tones.find((tone) => tone.id === editingToneId.value) ?? null);

function openEdit(tone: Tone): void {
    editingToneId.value = tone.id;
    editForm.defaults({
        name: tone.name,
        description: tone.description,
        is_active: tone.is_active,
        sort_order: tone.sort_order,
    });
    editForm.reset();
}

function cancelEdit(): void {
    editingToneId.value = null;
    editForm.reset();
    editForm.clearErrors();
}

function toggleActive(tone: Tone): void {
    router.patch(`/admin/tones/${tone.id}`, {
        name: tone.name,
        description: tone.description,
        is_active: !tone.is_active,
        sort_order: tone.sort_order,
    });
}

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(t('admin.deleteBuiltInToneConfirm'))) {
        event.preventDefault();
    }
}

function onDeleted(toneId: number): void {
    if (editingToneId.value === toneId) {
        cancelEdit();
    }
}
</script>

<template>
    <Head :title="t('pages.builtInTones')" />

    <div class="nova-admin-tones">
        <div class="nova-admin-tones-layout">
            <header class="nova-admin-tones-head">
                <h2 class="nova-admin-tones-title">{{ t('pages.builtInTones') }}</h2>
                <p class="nova-admin-tones-subtitle">{{ t('admin.tonesSubtitleLong') }}</p>
            </header>

            <div class="nova-admin-tones-summary-wrap">
                <span class="nova-admin-tones-summary">
                    {{ t('admin.tonesSummary', { total: summary.total, active: summary.active }) }}
                </span>
            </div>

            <div class="nova-card nova-admin-table-card nova-admin-tones-table-card">
                <div class="nova-hist-table-wrap">
                    <table class="nova-hist-table nova-admin-tones-table">
                        <colgroup>
                            <col class="nova-admin-tones-col-order" />
                            <col class="nova-admin-tones-col-name" />
                            <col />
                            <col class="nova-admin-tones-col-generator" />
                            <col class="nova-admin-tones-col-actions" />
                        </colgroup>
                        <thead>
                            <tr>
                                <th>{{ t('admin.colOrder') }}</th>
                                <th>{{ t('admin.toneName') }}</th>
                                <th>{{ t('admin.toneDescription') }}</th>
                                <th>{{ t('admin.onGenerator') }}</th>
                                <th aria-hidden="true" class="nova-admin-tones-th-actions" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="tone in tones" :key="tone.id">
                                <td>
                                    <span class="nova-admin-tones-order">{{ tone.sort_order }}</span>
                                </td>
                                <td class="nova-admin-tones-name">{{ tone.name }}</td>
                                <td class="nova-admin-tones-desc">{{ tone.description }}</td>
                                <td>
                                    <div class="nova-admin-tones-generator-cell">
                                        <label class="nova-admin-toggle">
                                            <input
                                                :checked="tone.is_active"
                                                type="checkbox"
                                                @change="toggleActive(tone)"
                                            />
                                            <span />
                                        </label>
                                        <span class="nova-admin-tones-on-label">
                                            {{ tone.is_active ? t('admin.generatorOn') : t('admin.generatorOff') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="nova-admin-tones-row-actions">
                                        <button
                                            class="nova-btn nova-admin-tones-edit-btn"
                                            type="button"
                                            @click="openEdit(tone)"
                                        >
                                            {{ t('admin.editToneShort') }}
                                        </button>
                                        <Form
                                            :action="`/admin/tones/${tone.id}`"
                                            method="delete"
                                            #default="{ processing }"
                                            @submit="confirmDelete"
                                            @success="onDeleted(tone.id)"
                                        >
                                            <button
                                                class="nova-btn nova-admin-tones-edit-btn nova-admin-tones-delete-btn"
                                                :disabled="processing"
                                                type="submit"
                                            >
                                                {{ t('admin.deleteToneShort') }}
                                            </button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="nova-card nova-admin-tones-panel">
                <template v-if="editingTone">
                    <h3 class="nova-admin-tones-panel-title">
                        {{ t('admin.editToneTitle', { name: editingTone.name }) }}
                    </h3>
                    <form
                        class="nova-admin-tones-form"
                        @submit.prevent="
                            editForm.patch(`/admin/tones/${editingTone.id}`, {
                                onSuccess: () => cancelEdit(),
                            })
                        "
                    >
                        <div class="nova-admin-tones-field">
                            <label class="form-label">{{ t('admin.toneFieldName') }}</label>
                            <input v-model="editForm.name" class="form-control" required />
                        </div>
                        <div class="nova-admin-tones-field">
                            <label class="form-label">{{ t('admin.toneFieldDescription') }}</label>
                            <textarea v-model="editForm.description" class="form-control" required rows="4" />
                        </div>
                        <div class="nova-admin-tones-form-footer">
                            <div class="nova-admin-tones-order-wrap">
                                <label class="form-label">{{ t('admin.colOrder') }}</label>
                                <input
                                    v-model.number="editForm.sort_order"
                                    class="form-control nova-admin-tones-order-input"
                                    type="number"
                                />
                            </div>
                            <div class="nova-admin-tones-active-wrap">
                                <label class="nova-admin-toggle">
                                    <input v-model="editForm.is_active" type="checkbox" />
                                    <span />
                                </label>
                                <span class="nova-admin-tones-active-label">{{ t('admin.toneActive') }}</span>
                            </div>
                        </div>
                        <div class="nova-admin-tones-form-actions">
                            <button class="nova-btn" type="button" @click="cancelEdit">{{ t('admin.cancelEdit') }}</button>
                            <button class="nova-btn nova-btn-primary" :disabled="editForm.processing" type="submit">
                                {{ t('settings.saveProfile') }}
                            </button>
                        </div>
                    </form>
                </template>
                <template v-else>
                    <h3 class="nova-admin-tones-panel-title">{{ t('admin.addToneTitle') }}</h3>
                    <p class="nova-admin-tones-panel-hint">{{ t('admin.addToneHint') }}</p>
                    <form
                        class="nova-admin-tones-form"
                        @submit.prevent="
                            createForm.post('/admin/tones', {
                                onSuccess: () => {
                                    createForm.reset();
                                    createForm.sort_order = nextSortOrder.value;
                                },
                            })
                        "
                    >
                        <div class="nova-admin-tones-field">
                            <label class="form-label">{{ t('admin.toneFieldName') }}</label>
                            <input
                                v-model="createForm.name"
                                class="form-control"
                                :placeholder="t('admin.toneNamePlaceholder')"
                                required
                            />
                        </div>
                        <div class="nova-admin-tones-field">
                            <label class="form-label">{{ t('admin.toneFieldDescription') }}</label>
                            <textarea
                                v-model="createForm.description"
                                class="form-control"
                                required
                                :placeholder="t('admin.toneDescriptionPlaceholder')"
                                rows="4"
                            />
                        </div>
                        <div class="nova-admin-tones-form-footer">
                            <div class="nova-admin-tones-order-wrap">
                                <label class="form-label">{{ t('admin.colOrder') }}</label>
                                <input
                                    v-model.number="createForm.sort_order"
                                    class="form-control nova-admin-tones-order-input"
                                    type="number"
                                />
                            </div>
                            <div class="nova-admin-tones-active-wrap">
                                <label class="nova-admin-toggle">
                                    <input v-model="createForm.is_active" type="checkbox" />
                                    <span />
                                </label>
                                <span class="nova-admin-tones-active-label">{{ t('admin.toneActive') }}</span>
                            </div>
                        </div>
                        <button
                            class="nova-btn nova-btn-primary nova-admin-tones-submit"
                            :disabled="createForm.processing"
                            type="submit"
                        >
                            {{ t('admin.addToneSubmit') }}
                        </button>
                    </form>
                </template>
            </aside>
        </div>
    </div>
</template>
