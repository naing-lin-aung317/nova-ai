<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: AppLayout });

type DashboardStats = {
    availableCredits: number;
    generations: number;
    customTones: number;
};

type LedgerRow = {
    id: number;
    type: string;
    label: string;
    amount: number;
    created_at: string | null;
};

const props = defineProps<{
    stats: DashboardStats;
    recentTransactions: LedgerRow[];
    recentContent: unknown[];
    lowCredits: boolean;
}>();

const { t } = useI18n();
const page = usePage();

const userName = computed(
    () => (page.props.auth as { user: AuthUser }).user.name,
);

function amountClass(amount: number): string {
    if (amount > 0) {
        return 'nova-pos';
    }

    if (amount < 0) {
        return 'nova-neg';
    }

    return 'nova-muted';
}

function formatAmount(amount: number): string {
    if (amount > 0) {
        return `+${amount}`;
    }

    return String(amount);
}
</script>

<template>
    <div>
        <div class="nova-row">
            <h2>{{ t('dashboard.welcome') }}, {{ userName }}</h2>
            <Link class="nova-btn nova-btn-primary" href="/generator">
                {{ t('dashboard.newContent') }}
            </Link>
        </div>

        <div v-if="lowCredits" class="nova-alert" style="border-color: #f5a524">
            {{ t('dashboard.lowCredits', { count: stats.availableCredits }) }}
            <Link href="/billing">{{ t('dashboard.buyCredits') }}</Link>
        </div>

        <div class="nova-grid nova-grid-3" style="margin-top: 16px">
            <div class="nova-card">
                <span class="nova-muted">{{ t('dashboard.availableCredits') }}</span>
                <p class="nova-big cr">{{ stats.availableCredits }}</p>
            </div>
            <div class="nova-card">
                <span class="nova-muted">{{ t('dashboard.generations') }}</span>
                <p class="nova-big">{{ stats.generations }}</p>
            </div>
            <div class="nova-card">
                <span class="nova-muted">{{ t('dashboard.customTones') }}</span>
                <p class="nova-big">{{ stats.customTones }}</p>
            </div>
        </div>

        <div class="nova-grid nova-grid-2" style="margin-top: 16px">
            <div class="nova-card">
                <div class="nova-row">
                    <h3>{{ t('dashboard.recentContent') }}</h3>
                    <Link href="/history">{{ t('dashboard.viewAll') }}</Link>
                </div>
                <p v-if="recentContent.length === 0" class="nova-muted" style="margin-top: 10px">
                    {{ t('dashboard.noContentYet') }}
                </p>
            </div>

            <div class="nova-card">
                <h3>{{ t('dashboard.creditActivity') }}</h3>
                <template v-if="recentTransactions.length">
                    <div
                        v-for="row in recentTransactions"
                        :key="row.id"
                        class="nova-row nova-li"
                        style="cursor: default"
                    >
                        <span>{{ row.label }}</span>
                        <b :class="amountClass(row.amount)">{{ formatAmount(row.amount) }}</b>
                    </div>
                </template>
                <p v-else class="nova-muted">{{ t('dashboard.noActivity') }}</p>
                <p class="nova-muted" style="margin-top: 10px">{{ t('dashboard.noChargeNote') }}</p>
            </div>
        </div>
    </div>
</template>
