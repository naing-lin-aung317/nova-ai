<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    logs: {
        data: Array<{
            id: number;
            action: string;
            actor_name: string | null;
            actor_email: string | null;
            subject_type: string | null;
            subject_id: number | null;
            reason: string | null;
            ip_address: string | null;
            created_at: string | null;
        }>;
    };
}>();

const { t } = useI18n();

const actionFilter = ref('all');
const actorSearch = ref('');

const actionOptions = computed(() => {
    const unique = [...new Set(props.logs.data.map((log) => log.action))];

    return ['all', ...unique.sort()];
});

const filteredLogs = computed(() =>
    props.logs.data.filter((log) => {
        const matchesAction = actionFilter.value === 'all' || log.action === actionFilter.value;
        const q = actorSearch.value.trim().toLowerCase();
        const matchesActor =
            q === '' ||
            (log.actor_email?.toLowerCase().includes(q) ?? false) ||
            (log.actor_name?.toLowerCase().includes(q) ?? false);

        return matchesAction && matchesActor;
    }),
);

function formatAction(action: string): string {
    return action.replaceAll('.', ' · ');
}

function actionTone(action: string): string {
    if (action.includes('created')) {
        return 'is-ok';
    }

    if (action.includes('suspended') || action.includes('failed')) {
        return 'is-danger';
    }

    return 'is-neutral';
}
</script>

<template>
    <Head :title="t('pages.audit')" />

    <AdminPageHeader :subtitle="t('admin.auditSubtitle')" :title="t('pages.audit')">
        <template #actions>
            <div class="nova-admin-audit-filters">
                <select v-model="actionFilter" class="form-select">
                    <option value="all">{{ t('admin.filterAllActions') }}</option>
                    <option v-for="action in actionOptions.filter((a) => a !== 'all')" :key="action" :value="action">
                        {{ formatAction(action) }}
                    </option>
                </select>
                <input v-model="actorSearch" class="form-control" :placeholder="t('admin.searchActor')" type="search" />
            </div>
        </template>
    </AdminPageHeader>

    <div class="nova-card nova-admin-table-card" style="margin-top: 8px">
        <div class="nova-hist-table-wrap">
            <table class="nova-hist-table">
                <thead>
                    <tr>
                        <th>{{ t('billing.colDate') }}</th>
                        <th>{{ t('admin.colAction') }}</th>
                        <th>{{ t('admin.colActor') }}</th>
                        <th>{{ t('admin.colReason') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="log in filteredLogs" :key="log.id">
                        <td class="nova-muted">
                            {{ log.created_at ? new Date(log.created_at).toLocaleString() : '—' }}
                        </td>
                        <td>
                            <span class="nova-badge" :class="actionTone(log.action)">{{ formatAction(log.action) }}</span>
                        </td>
                        <td>{{ log.actor_email ?? log.actor_name }}</td>
                        <td class="nova-muted">{{ log.reason ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
