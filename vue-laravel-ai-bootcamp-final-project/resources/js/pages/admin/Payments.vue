<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminFilterPills from '@/components/admin/AdminFilterPills.vue';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import { useStatusBadge } from '@/composables/useStatusBadge';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

type OrderRow = {
    id: number;
    user_name: string | null;
    user_email: string | null;
    package_name: string | null;
    amount_usd: string;
    credits: number;
    status: string;
    stripe_checkout_session_id: string | null;
    fulfilled_by_admin_at: string | null;
    created_at: string | null;
};

const props = defineProps<{
    orders: {
        data: OrderRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { status: string };
    summary: { paid_count: number; paid_total_usd: string };
}>();

const { t } = useI18n();
const { paymentLabel, paymentTone } = useStatusBadge();

const statusFilter = ref(props.filters.status || 'all');

watch(statusFilter, (value) => {
    router.get(
        '/admin/payments',
        { status: value === 'all' ? undefined : value },
        { preserveState: true, preserveScroll: true },
    );
});

const filterOptions = computed(() => [
    { value: 'all', label: t('admin.filterAll') },
    { value: 'pending', label: t('billing.status.pending') },
    { value: 'paid', label: t('billing.status.paid') },
]);
</script>

<template>
    <Head :title="t('pages.payments')" />

    <AdminPageHeader :subtitle="t('admin.paymentsSubtitle')" :title="t('pages.payments')">
        <template #badge>
            <span class="nova-badge is-ok">
                {{ t('admin.paymentsSummary', { count: summary.paid_count, amount: summary.paid_total_usd }) }}
            </span>
        </template>
    </AdminPageHeader>

    <AdminFilterPills v-model="statusFilter" :options="filterOptions" style="margin: 12px 0" />

    <div class="nova-card nova-admin-table-card">
        <div class="nova-hist-table-wrap">
            <table class="nova-hist-table">
                <thead>
                    <tr>
                        <th>{{ t('billing.colDate') }}</th>
                        <th>{{ t('auth.email') }}</th>
                        <th>{{ t('billing.colPackage') }}</th>
                        <th>{{ t('billing.colAmount') }}</th>
                        <th>{{ t('billing.colStatus') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="nova-muted">
                            {{ order.created_at ? new Date(order.created_at).toLocaleString() : '—' }}
                        </td>
                        <td>{{ order.user_email }}</td>
                        <td>{{ order.package_name }}</td>
                        <td><b>${{ order.amount_usd }}</b></td>
                        <td>
                            <span class="nova-badge" :class="`is-${paymentTone(order.status)}`">
                                {{ paymentLabel(order.status) }}
                            </span>
                        </td>
                        <td>
                            <Form
                                v-if="order.status === 'pending'"
                                :action="`/admin/payments/${order.id}/verify`"
                                method="post"
                                #default="{ processing }"
                            >
                                <button class="nova-btn-link" :disabled="processing" type="submit">
                                    {{ t('admin.verifyManually') }}
                                </button>
                            </Form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
