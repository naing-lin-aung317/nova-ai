<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps<{
    mismatchCount: number;
    mismatches: Array<{
        user_name: string | null;
        user_email: string | null;
        available: number;
        ledger_sum: number;
    }>;
    staleReservations: number;
    pendingReservations: number;
    webhooks: Array<{
        id: number;
        stripe_event_id: string;
        event_type: string;
        payment_order_id: number | null;
        processed_at: string;
    }>;
    allChecksClear: boolean;
}>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('pages.monitoring')" />

    <AdminPageHeader :subtitle="t('admin.monitoringSubtitle')" :title="t('pages.monitoring')">
        <template #badge>
            <span class="nova-badge" :class="allChecksClear ? 'is-ok' : 'is-warn'">
                {{ allChecksClear ? t('admin.allChecksClear') : t('admin.attentionNeeded') }}
            </span>
        </template>
    </AdminPageHeader>

    <div class="nova-grid nova-grid-3" style="margin-top: 12px">
        <div class="nova-card">
            <span class="nova-muted">{{ t('admin.ledgerMismatches') }}</span>
            <div class="nova-admin-stat-big">{{ mismatchCount }}</div>
            <span class="nova-badge" :class="mismatchCount === 0 ? 'is-ok' : 'is-danger'">
                {{ mismatchCount === 0 ? t('admin.balanced') : t('admin.attentionNeeded') }}
            </span>
        </div>
        <div class="nova-card">
            <span class="nova-muted">{{ t('admin.staleReservations') }}</span>
            <div class="nova-admin-stat-big">{{ staleReservations }}</div>
            <span class="nova-badge" :class="staleReservations === 0 ? 'is-ok' : 'is-warn'">
                {{ staleReservations === 0 ? t('admin.noneStuck') : t('admin.staleCount', { count: staleReservations }) }}
            </span>
        </div>
        <div class="nova-card">
            <span class="nova-muted">{{ t('admin.pendingReservations') }}</span>
            <div class="nova-admin-stat-big">{{ pendingReservations }}</div>
            <span class="nova-badge" :class="pendingReservations === 0 ? 'is-ok' : 'is-warn'">
                {{ pendingReservations === 0 ? t('admin.queueEmpty') : t('admin.inQueue') }}
            </span>
        </div>
    </div>

    <div v-if="mismatches.length" class="nova-card nova-admin-table-card" style="margin-top: 16px">
        <h3 style="margin: 12px 16px 0">{{ t('admin.mismatchDetails') }}</h3>
        <div class="nova-hist-table-wrap">
            <table class="nova-hist-table">
                <thead>
                    <tr>
                        <th>{{ t('auth.email') }}</th>
                        <th>{{ t('admin.colWallet') }}</th>
                        <th>{{ t('admin.colLedger') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(row, index) in mismatches" :key="index">
                        <td>{{ row.user_email }}</td>
                        <td>{{ row.available }}</td>
                        <td>{{ row.ledger_sum }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="nova-card nova-admin-table-card" style="margin-top: 16px">
        <h3 style="margin: 12px 16px 8px">{{ t('admin.recentWebhooks') }}</h3>
        <div class="nova-hist-table-wrap">
            <table class="nova-hist-table">
                <thead>
                    <tr>
                        <th>{{ t('admin.colEvent') }}</th>
                        <th>{{ t('admin.colReference') }}</th>
                        <th>{{ t('billing.colStatus') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="hook in webhooks" :key="hook.id">
                        <td><code class="nova-admin-code">{{ hook.event_type }}</code></td>
                        <td class="nova-muted"><code class="nova-admin-code">{{ hook.stripe_event_id }}</code></td>
                        <td><span class="nova-badge is-ok">{{ t('admin.webhookReceived') }}</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
