<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { initialsFromName } from '@/utils/initials';

defineOptions({ layout: AdminLayout });

defineProps<{
    stats: {
        users: number;
        verified_users: number;
        generations_24h: number;
        failed_generations_24h: number;
        payments_paid_30d: number;
        revenue_usd_30d: string;
    };
    recentUsers: Array<{ id: number; name: string; email: string; created_at: string | null }>;
    recentPaidOrders: Array<{
        id: number;
        user_name: string | null;
        package_name: string | null;
        amount_usd: string;
        paid_at: string | null;
    }>;
}>();

const { t } = useI18n();
</script>

<template>
    <Head :title="t('pages.overview')" />

    <div class="nova-admin-overview">
        <header class="nova-admin-overview-head">
            <h2 class="nova-admin-overview-title">{{ t('pages.overview') }}</h2>
            <p class="nova-admin-overview-subtitle">{{ t('admin.overviewSubtitle') }}</p>
        </header>

        <div class="nova-admin-overview-stats">
            <div class="nova-card nova-admin-overview-stat-card">
                <span class="nova-admin-overview-stat-label">{{ t('admin.statUsers') }}</span>
                <div class="nova-admin-overview-stat-value">{{ stats.users }}</div>
                <div class="nova-admin-overview-stat-foot">
                    <span class="nova-admin-overview-stat-badge is-ok">
                        {{ t('admin.statVerified', { count: stats.verified_users }) }}
                    </span>
                </div>
            </div>
            <div class="nova-card nova-admin-overview-stat-card">
                <span class="nova-admin-overview-stat-label">{{ t('admin.statGenerations24h') }}</span>
                <div class="nova-admin-overview-stat-value">{{ stats.generations_24h }}</div>
                <div class="nova-admin-overview-stat-foot">
                    <span v-if="stats.failed_generations_24h > 0" class="nova-admin-overview-stat-badge is-danger">
                        {{ t('admin.statFailed24h', { count: stats.failed_generations_24h }) }}
                    </span>
                </div>
            </div>
            <div class="nova-card nova-admin-overview-stat-card">
                <span class="nova-admin-overview-stat-label">{{ t('admin.statPaid30d') }}</span>
                <div class="nova-admin-overview-stat-value">{{ stats.payments_paid_30d }}</div>
                <div class="nova-admin-overview-stat-foot">
                    <span class="nova-admin-overview-stat-meta">
                        {{ t('admin.revenueTotal', { amount: stats.revenue_usd_30d }) }}
                    </span>
                </div>
            </div>
        </div>

        <div class="nova-admin-overview-panels">
            <div class="nova-card nova-admin-overview-panel-card">
                <div class="nova-admin-overview-panel-head">
                    <h3 class="nova-admin-overview-panel-title">{{ t('admin.recentUsers') }}</h3>
                    <Link class="nova-admin-overview-view-all" href="/admin/users">{{ t('admin.viewAll') }}</Link>
                </div>
                <ul class="nova-admin-overview-feed">
                    <li v-for="user in recentUsers" :key="user.id">
                        <span class="nova-admin-overview-avatar">{{ initialsFromName(user.name) }}</span>
                        <span class="nova-admin-overview-feed-text">
                            <span class="nova-admin-overview-feed-name">{{ user.name }}</span>
                            <span class="nova-admin-overview-feed-sub">{{ user.email }}</span>
                        </span>
                    </li>
                </ul>
            </div>
            <div class="nova-card nova-admin-overview-panel-card">
                <div class="nova-admin-overview-panel-head">
                    <h3 class="nova-admin-overview-panel-title">{{ t('admin.recentPayments') }}</h3>
                    <Link class="nova-admin-overview-view-all" href="/admin/payments">{{ t('admin.viewAll') }}</Link>
                </div>
                <ul class="nova-admin-overview-feed nova-admin-overview-feed-payments">
                    <li v-for="order in recentPaidOrders" :key="order.id">
                        <span class="nova-admin-overview-feed-text">
                            <span class="nova-admin-overview-feed-name">{{ order.user_name }}</span>
                            <span class="nova-admin-overview-feed-sub">{{ order.package_name }}</span>
                        </span>
                        <span class="nova-admin-overview-payment-amount">${{ order.amount_usd }}</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
