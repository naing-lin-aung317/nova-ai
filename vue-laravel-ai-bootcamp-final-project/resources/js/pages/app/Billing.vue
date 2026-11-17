<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { useWallet } from '@/composables/useWallet';
import AppLayout from '@/layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

type CreditPackageProps = {
    id: number;
    name: string;
    slug: string;
    credits: number;
    price_cents: number;
    price_usd: string;
    highlight: boolean;
};

type PaymentOrderRow = {
    id: number;
    package_name: string | null;
    amount_usd: string;
    credits: number;
    status: string;
    created_at: string | null;
};

const props = defineProps<{
    packages: CreditPackageProps[];
    orders: PaymentOrderRow[];
    stripeFake: boolean;
}>();

const { t } = useI18n();
const { availableCredits } = useWallet();
const page = usePage();

const flashStatus = computed(
    () => (page.props.flash as { status?: string | null } | undefined)?.status ?? undefined,
);

function defaultPackageId(): number {
    const highlighted = props.packages.find((pkg) => pkg.highlight);
    const creator = props.packages.find((pkg) => pkg.slug === 'creator');

    return highlighted?.id ?? creator?.id ?? props.packages[0]?.id ?? 0;
}

const selectedPackageId = ref(defaultPackageId());

watch(
    () => props.packages,
    () => {
        if (!props.packages.some((pkg) => pkg.id === selectedPackageId.value)) {
            selectedPackageId.value = defaultPackageId();
        }
    },
);

const selectedPackage = computed(() =>
    props.packages.find((pkg) => pkg.id === selectedPackageId.value) ?? props.packages[0],
);

function perCreditAmount(priceUsd: string, credits: number): string {
    const price = Number.parseFloat(priceUsd);

    return `$${(price / credits).toFixed(2)}`;
}

function balanceAfterPurchase(pkg: CreditPackageProps): number {
    return availableCredits.value + pkg.credits;
}

function statusLabel(status: string): string {
    const key = `billing.status.${status}`;

    return t(key) !== key ? t(key) : status;
}

function selectPackage(id: number): void {
    selectedPackageId.value = id;
}
</script>

<template>
    <Head :title="t('pages.billing')" />

    <div class="nova-billing">
        <header class="nova-billing-head">
            <h1 class="nova-billing-title">{{ t('pages.billing') }}</h1>
            <p class="nova-billing-subtitle">{{ t('shell.pricingSub') }}</p>
        </header>

        <p v-if="flashStatus" class="nova-alert nova-billing-flash">{{ t(flashStatus) }}</p>

        <div class="nova-billing-layout">
            <section class="nova-billing-choose" aria-labelledby="billing-choose-heading">
                <h2 id="billing-choose-heading" class="nova-billing-step-title">
                    {{ t('billing.choosePackage') }}
                </h2>
                <div class="nova-billing-packages" role="radiogroup" :aria-label="t('billing.choosePackage')">
                    <button
                        v-for="pkg in packages"
                        :key="pkg.id"
                        type="button"
                        role="radio"
                        class="nova-billing-package"
                        :class="{ 'is-selected': selectedPackageId === pkg.id }"
                        :aria-checked="selectedPackageId === pkg.id"
                        @click="selectPackage(pkg.id)"
                    >
                        <span class="nova-billing-package-radio" aria-hidden="true" />
                        <span class="nova-billing-package-body">
                            <span class="nova-billing-package-name-row">
                                <span class="nova-billing-package-name">{{ pkg.name }}</span>
                                <span v-if="pkg.highlight" class="nova-billing-package-badge">
                                    {{ t('shell.bestValue') }}
                                </span>
                            </span>
                            <span class="nova-billing-package-meta">
                                {{
                                    t('billing.packageCreditsLine', {
                                        credits: pkg.credits,
                                        perCredit: perCreditAmount(pkg.price_usd, pkg.credits),
                                    })
                                }}
                            </span>
                        </span>
                        <span class="nova-billing-package-price">${{ pkg.price_usd }}</span>
                    </button>
                </div>
            </section>

            <aside v-if="selectedPackage" class="nova-billing-review-wrap" aria-labelledby="billing-review-heading">
                <div class="nova-card nova-billing-review">
                    <h2 id="billing-review-heading" class="nova-billing-step-title">
                        {{ t('billing.reviewPay') }}
                    </h2>
                    <p class="nova-billing-review-credits">
                        {{ t('billing.creditsCount', { count: selectedPackage.credits }) }}
                    </p>
                    <dl class="nova-billing-review-rows">
                        <div class="nova-billing-review-row">
                            <dt>{{ t('billing.colPackage') }}</dt>
                            <dd>{{ selectedPackage.name }}</dd>
                        </div>
                        <div class="nova-billing-review-row">
                            <dt>{{ t('billing.pricePerCredit') }}</dt>
                            <dd>
                                {{ perCreditAmount(selectedPackage.price_usd, selectedPackage.credits) }}
                            </dd>
                        </div>
                        <div class="nova-billing-review-row">
                            <dt>{{ t('billing.balanceAfter') }}</dt>
                            <dd>
                                {{ t('billing.balanceCredits', { count: balanceAfterPurchase(selectedPackage) }) }}
                            </dd>
                        </div>
                    </dl>
                    <p class="nova-billing-review-total">${{ selectedPackage.price_usd }}</p>
                    <Form
                        :action="`/billing/checkout/${selectedPackage.id}`"
                        method="post"
                        class="nova-billing-checkout-form"
                        #default="{ processing }"
                    >
                        <button
                            class="nova-btn nova-btn-primary nova-billing-buy w-100"
                            :disabled="processing"
                            type="submit"
                        >
                            {{ t('shell.buyStripe') }}
                        </button>
                    </Form>
                    <p v-if="stripeFake" class="nova-billing-test-note">
                        {{ t('billing.fakeModeNote') }}
                    </p>
                </div>
            </aside>
        </div>

        <section class="nova-billing-history" aria-labelledby="billing-history-heading">
            <h2 id="billing-history-heading" class="nova-billing-history-title">
                {{ t('billing.paymentHistory') }}
            </h2>
            <div v-if="orders.length === 0" class="nova-card nova-billing-history-empty">
                <svg
                    class="nova-billing-history-icon"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden="true"
                >
                    <path
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                        stroke="currentColor"
                        stroke-width="1.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>
                <p>{{ t('billing.emptyHistory') }}</p>
            </div>
            <div v-else class="nova-card nova-hist-table-card">
                <div class="nova-hist-table-wrap">
                    <table class="nova-hist-table">
                        <thead>
                            <tr>
                                <th>{{ t('billing.colDate') }}</th>
                                <th>{{ t('billing.colPackage') }}</th>
                                <th>{{ t('billing.colAmount') }}</th>
                                <th>{{ t('billing.colCredits') }}</th>
                                <th>{{ t('billing.colStatus') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders" :key="order.id">
                                <td class="nova-muted" :data-label="t('billing.colDate')">
                                    {{
                                        order.created_at
                                            ? new Date(order.created_at).toLocaleString()
                                            : '—'
                                    }}
                                </td>
                                <td :data-label="t('billing.colPackage')">
                                    {{ order.package_name ?? '—' }}
                                </td>
                                <td :data-label="t('billing.colAmount')">${{ order.amount_usd }}</td>
                                <td :data-label="t('billing.colCredits')">{{ order.credits }}</td>
                                <td :data-label="t('billing.colStatus')">
                                    <span class="nova-badge">{{ statusLabel(order.status) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
