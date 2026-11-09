<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import type { GenerationListItem } from '@/types/generation';

defineOptions({ layout: AppLayout });

type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};

type StatusTab = 'completed' | 'failed' | 'stopped' | 'streaming' | 'all';

const props = defineProps<{
    generations: Paginated<GenerationListItem>;
    statusCounts: Record<StatusTab, number>;
    filters: {
        q: string;
        content_type: string;
        language: string;
        status: string;
    };
}>();

const { t } = useI18n();

const activeStatus = computed(() => props.filters.status as StatusTab);

const tabs = computed(() => [
    { key: 'completed' as const, label: t('history.tabCompleted') },
    { key: 'failed' as const, label: t('history.tabFailed') },
    { key: 'stopped' as const, label: t('history.tabStopped') },
    { key: 'streaming' as const, label: t('history.tabStreaming') },
    { key: 'all' as const, label: t('history.tabAll') },
]);

function setStatusTab(status: StatusTab): void {
    router.get(
        '/history',
        { status },
        { preserveState: true, replace: true, preserveScroll: true },
    );
}

function formatDate(iso: string | null): string {
    if (! iso) {
        return '—';
    }

    return new Date(iso).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function briefPreview(topic: string, max = 56): string {
    const trimmed = topic.trim();

    if (trimmed.length <= max) {
        return trimmed;
    }

    return `${trimmed.slice(0, max)}…`;
}

function statusBadgeClass(status: string): string {
    if (status === 'completed') {
        return 'nova-hist-badge is-completed';
    }

    if (status === 'failed') {
        return 'nova-hist-badge is-failed';
    }

    if (status === 'stopped') {
        return 'nova-hist-badge is-stopped';
    }

    if (status === 'streaming' || status === 'pending') {
        return 'nova-hist-badge is-streaming';
    }

    return 'nova-hist-badge';
}

function deleteRow(row: GenerationListItem): void {
    if (! row.can_delete) {
        return;
    }

    if (! window.confirm(t('history.deleteConfirm'))) {
        return;
    }

    router.delete(`/generations/${row.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="nova-hist">
        <header class="nova-hist-header">
            <div>
                <h2 class="nova-hist-title">{{ t('history.pageTitle') }}</h2>
                <p class="nova-muted" style="margin-top: 8px">{{ t('history.subtitle') }}</p>
            </div>
            <Link class="nova-btn nova-btn-primary" href="/generator">
                {{ t('history.newGeneration') }}
            </Link>
        </header>

        <div class="nova-hist-tabs" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                class="nova-hist-tab"
                :class="{ 'is-active': activeStatus === tab.key }"
                :aria-selected="activeStatus === tab.key"
                @click="setStatusTab(tab.key)"
            >
                {{ tab.label }}
                <span class="nova-hist-tab-count">({{ statusCounts[tab.key] ?? 0 }})</span>
            </button>
        </div>

        <div class="nova-card nova-hist-table-card">
            <div v-if="generations.data.length === 0" class="nova-hist-empty">
                {{ t('history.empty') }}
            </div>

            <div v-else class="nova-hist-table-wrap">
                <table class="nova-hist-table">
                    <thead>
                        <tr>
                            <th>{{ t('history.colType') }}</th>
                            <th>{{ t('history.colBrief') }}</th>
                            <th>{{ t('history.colTone') }}</th>
                            <th>{{ t('history.colStatus') }}</th>
                            <th>{{ t('history.colCredits') }}</th>
                            <th>{{ t('history.colDate') }}</th>
                            <th class="nova-hist-th-actions">{{ t('history.colActions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in generations.data" :key="row.id">
                            <td :data-label="t('history.colType')">
                                <span class="nova-hist-type-text">
                                    {{ t(`generator.types.${row.content_type}`) }}
                                </span>
                            </td>
                            <td :data-label="t('history.colBrief')">
                                <Link :href="row.detail_url" class="nova-hist-brief-link" :title="row.topic">
                                    {{ briefPreview(row.topic) }}
                                </Link>
                            </td>
                            <td :data-label="t('history.colTone')" class="nova-hist-tone">
                                {{ row.tone_label ?? '—' }}
                            </td>
                            <td :data-label="t('history.colStatus')">
                                <span :class="statusBadgeClass(row.status)">
                                    {{ t(`history.statuses.${row.status}`) }}
                                </span>
                            </td>
                            <td :data-label="t('history.colCredits')" class="nova-hist-credits">
                                {{ row.credits_charged }}
                            </td>
                            <td :data-label="t('history.colDate')" class="nova-hist-date">
                                {{ formatDate(row.created_at) }}
                            </td>
                            <td :data-label="t('history.colActions')">
                                <div class="nova-hist-actions">
                                    <Link
                                        :href="row.detail_url"
                                        class="nova-hist-action-btn"
                                        :title="t('history.viewAction')"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </Link>
                                    <button
                                        type="button"
                                        class="nova-hist-action-btn is-delete"
                                        :title="t('history.deleteAction')"
                                        :disabled="!row.can_delete"
                                        @click="deleteRow(row)"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M3 6h18" />
                                            <path d="M8 6V4h8v2" />
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6" />
                                            <path d="M10 11v6M14 11v6" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                v-if="generations.links?.length > 3"
                class="nova-hist-pagination"
            >
                <template v-for="(link, index) in generations.links" :key="index">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="nova-hist-page-link"
                        :class="{ 'is-active': link.active }"
                        preserve-scroll
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="nova-hist-page-link is-disabled"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>
</template>
