<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

type Pkg = {
    id: number;
    name: string;
    slug: string;
    credits: number;
    price_cents: number;
    price_usd: string;
    is_visible: boolean;
    sort_order: number;
    sold_count: number;
    highlight: boolean;
};

const props = defineProps<{
    packages: Pkg[];
    summary: { total: number; visible: number; cheapest_per_credit: string };
}>();

const { t } = useI18n();

const editingPackageId = ref<number | null>(null);
const togglingId = ref<number | null>(null);

const createForm = useForm({
    name: '',
    credits: 20,
    price_usd: 9.99,
    is_visible: true,
});

const editForm = useForm({
    name: '',
    credits: 0,
    price_usd: 0,
    is_visible: true as boolean,
    sort_order: 0,
});

const editingPackage = computed(() =>
    props.packages.find((pkg) => pkg.id === editingPackageId.value) ?? null,
);

const cheapestPerCredit = computed(() => Number.parseFloat(props.summary.cheapest_per_credit) || 0);

function pricePerCredit(priceUsd: string, credits: number): number {
    return Number.parseFloat(priceUsd) / Math.max(1, credits);
}

function perCreditLabel(priceUsd: string, credits: number): string {
    return t('shell.perCredit', {
        amount: `$${pricePerCredit(priceUsd, credits).toFixed(2)}`,
    });
}

function perCreditBarWidth(priceUsd: string, credits: number): number {
    const ppc = pricePerCredit(priceUsd, credits);
    const cheapest = cheapestPerCredit.value;

    if (ppc <= 0 || cheapest <= 0) {
        return 0;
    }

    return Math.min(100, Math.round((cheapest / ppc) * 100));
}

function isExpensivePerCredit(priceUsd: string, credits: number): boolean {
    const ppc = pricePerCredit(priceUsd, credits);
    const cheapest = cheapestPerCredit.value;

    return cheapest > 0 && ppc > cheapest * 1.8;
}

const createPerCredit = computed(() => {
    if (createForm.credits < 1 || createForm.price_usd <= 0) {
        return null;
    }

    return createForm.price_usd / createForm.credits;
});

const editPerCredit = computed(() => {
    if (editForm.credits < 1 || editForm.price_usd <= 0) {
        return null;
    }

    return editForm.price_usd / editForm.credits;
});

function openCreate(): void {
    editingPackageId.value = null;
    createForm.clearErrors();
    createForm.reset();
}

function openEdit(pkg: Pkg): void {
    editingPackageId.value = pkg.id;
    editForm.clearErrors();
    editForm.defaults({
        name: pkg.name,
        credits: pkg.credits,
        price_usd: Number.parseFloat(pkg.price_usd),
        is_visible: pkg.is_visible,
        sort_order: pkg.sort_order,
    });
    editForm.reset();
}

function cancelPanel(): void {
    editingPackageId.value = null;
    createForm.reset();
    createForm.clearErrors();
    editForm.reset();
    editForm.clearErrors();
}

function toggleVisible(pkg: Pkg, visible: boolean): void {
    togglingId.value = pkg.id;

    router.patch(
        `/admin/packages/${pkg.id}`,
        {
            name: pkg.name,
            credits: pkg.credits,
            price_usd: Number.parseFloat(pkg.price_usd),
            is_visible: visible,
            sort_order: pkg.sort_order,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                togglingId.value = null;
            },
        },
    );
}

function submitCreate(): void {
    createForm.post('/admin/packages', {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            editingPackageId.value = null;
        },
    });
}

function submitEdit(): void {
    if (! editingPackage.value) {
        return;
    }

    editForm.patch(`/admin/packages/${editingPackage.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingPackageId.value = null;
        },
    });
}
</script>

<template>
    <Head :title="t('pages.packages')" />

    <div class="nova-admin-packages">
        <header class="nova-admin-packages-toolbar">
            <div class="nova-admin-packages-head">
                <h2 class="nova-admin-packages-title">{{ t('pages.packages') }}</h2>
                <p class="nova-admin-packages-subtitle">{{ t('admin.packagesSubtitleLong') }}</p>
            </div>
            <button
                class="nova-btn nova-btn-primary nova-admin-packages-new"
                type="button"
                @click="openCreate"
            >
                {{ t('admin.newPackage') }}
            </button>
        </header>

        <p class="nova-admin-packages-pills" role="list">
            <span class="nova-admin-packages-pill" role="listitem">
                {{ t('admin.packagesCount', { count: summary.total }) }}
            </span>
            <span class="nova-admin-packages-pill" role="listitem">
                {{ t('admin.visibleOnBillingCount', { count: summary.visible }) }}
            </span>
            <span class="nova-admin-packages-pill" role="listitem">
                {{ t('admin.cheapestPerCreditLine', { amount: `$${summary.cheapest_per_credit}` }) }}
            </span>
        </p>

        <div class="nova-admin-packages-layout">
            <div class="nova-card nova-admin-packages-table-card">
                <div class="nova-admin-packages-table-wrap">
                    <table class="nova-admin-packages-table">
                        <thead>
                            <tr>
                                <th>{{ t('admin.colPackageUpper') }}</th>
                                <th>{{ t('admin.colCreditsUpper') }}</th>
                                <th>{{ t('admin.colPriceUpper') }}</th>
                                <th>{{ t('admin.colPerCreditUpper') }}</th>
                                <th>{{ t('admin.colStatusUpper') }}</th>
                                <th class="nova-admin-packages-th-action">{{ t('admin.colActionUpper') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="pkg in packages"
                                :key="pkg.id"
                                :class="{
                                    'is-editing': editingPackageId === pkg.id,
                                    'is-dim': ! pkg.is_visible,
                                }"
                            >
                                <td>
                                    <div class="nova-admin-packages-package-cell">
                                        <div class="nova-admin-packages-package-name-row">
                                            <span class="nova-admin-packages-table-name">{{ pkg.name }}</span>
                                            <span
                                                v-if="pkg.highlight"
                                                class="nova-admin-package-badge is-featured"
                                            >
                                                {{ t('shell.bestValue') }}
                                            </span>
                                        </div>
                                        <p
                                            v-if="isExpensivePerCredit(pkg.price_usd, pkg.credits)"
                                            class="nova-admin-packages-warning"
                                        >
                                            {{ t('admin.packageExpensiveWarning') }}
                                        </p>
                                    </div>
                                </td>
                                <td class="nova-admin-packages-num">{{ pkg.credits }}</td>
                                <td class="nova-admin-packages-price">${{ pkg.price_usd }}</td>
                                <td>
                                    <div class="nova-admin-packages-per-credit-cell">
                                        <span class="nova-admin-packages-per-credit-text">
                                            {{ perCreditLabel(pkg.price_usd, pkg.credits) }}
                                        </span>
                                        <span
                                            class="nova-admin-packages-per-credit-bar"
                                            role="presentation"
                                        >
                                            <span
                                                class="nova-admin-packages-per-credit-fill"
                                                :style="{ width: `${perCreditBarWidth(pkg.price_usd, pkg.credits)}%` }"
                                            />
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div class="nova-admin-packages-status-cell">
                                        <label class="nova-admin-toggle nova-admin-packages-toggle">
                                            <input
                                                type="checkbox"
                                                :checked="pkg.is_visible"
                                                :disabled="togglingId === pkg.id"
                                                @change="toggleVisible(pkg, ($event.target as HTMLInputElement).checked)"
                                            />
                                            <span />
                                        </label>
                                        <span
                                            class="nova-admin-packages-visible-label"
                                            :class="{ 'is-on': pkg.is_visible }"
                                        >
                                            {{ t('admin.visibleShort') }}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <button
                                        class="nova-btn nova-admin-packages-edit-btn"
                                        type="button"
                                        @click="openEdit(pkg)"
                                    >
                                        {{ t('admin.editPackageShort') }}
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <aside class="nova-card nova-admin-packages-side">
                <template v-if="editingPackage">
                    <h3 class="nova-admin-packages-side-title">
                        {{ t('admin.editPackageTitle', { name: editingPackage.name }) }}
                    </h3>
                    <form class="nova-admin-packages-side-form" @submit.prevent="submitEdit">
                        <label class="form-label">{{ t('admin.packageName') }}</label>
                        <input v-model="editForm.name" class="form-control" required />

                        <div class="nova-admin-packages-side-duo">
                            <div>
                                <label class="form-label">{{ t('admin.packageCredits') }}</label>
                                <input v-model.number="editForm.credits" class="form-control" required type="number" />
                            </div>
                            <div>
                                <label class="form-label">{{ t('admin.packagePriceUsd') }}</label>
                                <input
                                    v-model.number="editForm.price_usd"
                                    class="form-control"
                                    required
                                    step="0.01"
                                    type="number"
                                />
                            </div>
                        </div>

                        <div v-if="editPerCredit !== null" class="nova-admin-packages-calc-box">
                            <p>
                                {{ t('admin.packageWorksOutLead') }}
                                <strong>${{ editPerCredit.toFixed(2) }} {{ t('admin.perCreditSuffix') }}</strong>
                            </p>
                            <p class="nova-admin-packages-calc-sub">
                                {{
                                    t('admin.cheapestToday', {
                                        amount: `$${summary.cheapest_per_credit}`,
                                    })
                                }}
                            </p>
                        </div>

                        <label class="form-label" style="margin-top: 14px">{{ t('admin.packageSort') }}</label>
                        <input v-model.number="editForm.sort_order" class="form-control" required type="number" />

                        <div class="nova-admin-packages-visible-row">
                            <label class="nova-admin-toggle">
                                <input v-model="editForm.is_visible" type="checkbox" />
                                <span />
                            </label>
                            <span>{{ t('admin.visibleOnBillingPage') }}</span>
                        </div>

                        <div class="nova-admin-packages-side-actions">
                            <button class="nova-btn nova-admin-packages-cancel-btn" type="button" @click="cancelPanel">
                                {{ t('admin.cancelEdit') }}
                            </button>
                            <button
                                class="nova-btn nova-btn-primary nova-admin-packages-create-btn"
                                :disabled="editForm.processing"
                                type="submit"
                            >
                                {{ t('settings.saveProfile') }}
                            </button>
                        </div>
                    </form>
                </template>
                <template v-else>
                    <h3 class="nova-admin-packages-side-title">{{ t('admin.newPackagePanel') }}</h3>
                    <p class="nova-admin-packages-side-hint">{{ t('admin.newPackagePanelHint') }}</p>
                    <form class="nova-admin-packages-side-form" @submit.prevent="submitCreate">
                        <label class="form-label">{{ t('admin.packageName') }}</label>
                        <input
                            v-model="createForm.name"
                            class="form-control"
                            required
                            :placeholder="t('admin.packageNamePlaceholder')"
                        />

                        <div class="nova-admin-packages-side-duo">
                            <div>
                                <label class="form-label">{{ t('admin.packageCredits') }}</label>
                                <input
                                    v-model.number="createForm.credits"
                                    class="form-control"
                                    min="1"
                                    required
                                    type="number"
                                />
                            </div>
                            <div>
                                <label class="form-label">{{ t('admin.packagePriceUsd') }}</label>
                                <input
                                    v-model.number="createForm.price_usd"
                                    class="form-control"
                                    min="1"
                                    required
                                    step="0.01"
                                    type="number"
                                />
                            </div>
                        </div>

                        <div v-if="createPerCredit !== null" class="nova-admin-packages-calc-box">
                            <p>
                                {{ t('admin.packageWorksOutLead') }}
                                <strong>${{ createPerCredit.toFixed(2) }} {{ t('admin.perCreditSuffix') }}</strong>
                            </p>
                            <p class="nova-admin-packages-calc-sub">
                                {{
                                    t('admin.cheapestToday', {
                                        amount: `$${summary.cheapest_per_credit}`,
                                    })
                                }}
                            </p>
                        </div>

                        <div class="nova-admin-packages-visible-row">
                            <label class="nova-admin-toggle">
                                <input v-model="createForm.is_visible" type="checkbox" />
                                <span />
                            </label>
                            <span>{{ t('admin.visibleOnBillingPage') }}</span>
                        </div>

                        <div class="nova-admin-packages-side-actions">
                            <button class="nova-btn nova-admin-packages-cancel-btn" type="button" @click="cancelPanel">
                                {{ t('admin.cancelEdit') }}
                            </button>
                            <button
                                class="nova-btn nova-btn-primary nova-admin-packages-create-btn"
                                :disabled="createForm.processing"
                                type="submit"
                            >
                                {{ t('admin.createPackageSubmit') }}
                            </button>
                        </div>
                    </form>
                </template>
            </aside>
        </div>
    </div>
</template>