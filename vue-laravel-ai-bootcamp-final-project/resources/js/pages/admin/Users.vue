<script setup lang="ts">
import { Form, Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useStatusBadge } from '@/composables/useStatusBadge';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { initialsFromName } from '@/utils/initials';

defineOptions({ layout: AdminLayout });

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    email_verified: boolean;
    available_credits: number;
    generations_count: number;
    created_at: string | null;
};

const props = defineProps<{
    users: {
        data: UserRow[];
        links: Array<{ url: string | null; label: string; active: boolean }>;
    };
    filters: { q: string };
    maxAdjustment: number;
    summary: { total: number; active: number };
}>();

const { t } = useI18n();
const page = usePage();
const { userStatusLabel, userStatusTone } = useStatusBadge();

const flashStatus = computed(
    () => (page.props.flash as { status?: string | null } | undefined)?.status ?? undefined,
);

const verifyingId = ref<number | null>(null);

const search = ref(props.filters.q);
const expandAdjustId = ref<number | null>(null);

const adjustForm = useForm({
    delta: 5,
    reason: '',
});

function submitSearch(): void {
    router.get('/admin/users', { q: search.value || undefined }, { preserveState: true });
}

function toggleAdjust(userId: number): void {
    expandAdjustId.value = expandAdjustId.value === userId ? null : userId;
    adjustForm.reset();
    adjustForm.clearErrors();
}

function submitAdjustment(userId: number): void {
    adjustForm.post(`/admin/users/${userId}/credits`, {
        preserveScroll: true,
        onSuccess: () => {
            expandAdjustId.value = null;
            adjustForm.reset();
        },
    });
}

function canVerifyEmail(user: UserRow): boolean {
    return user.status === 'pending_verification' && ! user.email_verified;
}

function confirmVerifyEmail(user: UserRow): void {
    if (! canVerifyEmail(user) || verifyingId.value !== null) {
        return;
    }

    const ok = window.confirm(
        t('admin.verifyEmailConfirm', { name: user.name, email: user.email }),
    );

    if (! ok) {
        return;
    }

    verifyingId.value = user.id;

    router.post(
        `/admin/users/${user.id}/verify-email`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                verifyingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="t('pages.users')" />

    <div class="nova-admin-users">
        <header class="nova-admin-users-toolbar">
            <div class="nova-admin-users-head">
                <h2 class="nova-admin-users-title">{{ t('pages.users') }}</h2>
                <p class="nova-admin-users-meta">
                    {{ t('admin.usersSummary', { total: summary.total, active: summary.active }) }}
                </p>
            </div>

            <form class="nova-admin-users-search" @submit.prevent="submitSearch">
                <input
                    v-model="search"
                    class="form-control nova-admin-users-search-input"
                    :placeholder="t('admin.searchUsers')"
                    type="search"
                />
                <button class="nova-btn nova-btn-primary nova-admin-users-search-btn" type="submit">
                    {{ t('history.search') }}
                </button>
            </form>
        </header>

        <p v-if="flashStatus" class="nova-alert nova-admin-users-flash">
            {{ t(flashStatus) }}
        </p>

        <div class="nova-card nova-admin-table-card nova-admin-users-table-card">
            <div class="nova-hist-table-wrap">
                <table class="nova-hist-table nova-admin-users-table">
                    <colgroup>
                        <col class="nova-admin-users-col-name" />
                        <col class="nova-admin-users-col-email" />
                        <col class="nova-admin-users-col-status" />
                        <col class="nova-admin-users-col-credits" />
                        <col class="nova-admin-users-col-actions" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th>{{ t('admin.colNameUpper') }}</th>
                            <th>{{ t('admin.colEmailUpper') }}</th>
                            <th>{{ t('admin.colStatusUpper') }}</th>
                            <th>{{ t('admin.colCreditsUpper') }}</th>
                            <th class="nova-admin-users-th-actions" aria-hidden="true" />
                        </tr>
                    </thead>
                    <tbody>
                        <template v-for="user in users.data" :key="user.id">
                            <tr>
                                <td>
                                    <div class="nova-admin-user-name">
                                        <span class="nova-admin-avatar">{{ initialsFromName(user.name) }}</span>
                                        <span class="nova-admin-user-name-text">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="nova-admin-users-email">{{ user.email }}</td>
                                <td>
                                    <span class="nova-badge" :class="`is-${userStatusTone(user.status)}`">
                                        {{ userStatusLabel(user.status) }}
                                    </span>
                                </td>
                                <td class="nova-admin-users-credits">{{ user.available_credits }}</td>
                                <td>
                                    <div class="nova-admin-users-row-actions">
                                        <button
                                            v-if="canVerifyEmail(user)"
                                            class="nova-btn nova-btn-primary nova-admin-users-verify-btn"
                                            type="button"
                                            :disabled="verifyingId === user.id"
                                            @click="confirmVerifyEmail(user)"
                                        >
                                            {{ t('admin.verifyEmail') }}
                                        </button>
                                        <button
                                            class="nova-btn nova-admin-users-outline-btn"
                                            type="button"
                                            @click="toggleAdjust(user.id)"
                                        >
                                            {{ t('admin.adjustCredits') }}
                                        </button>
                                        <Form
                                            v-if="user.status !== 'suspended'"
                                            :action="`/admin/users/${user.id}/status`"
                                            method="patch"
                                            #default="{ processing }"
                                        >
                                            <input name="status" type="hidden" value="suspended" />
                                            <button
                                                class="nova-btn nova-admin-users-outline-btn nova-admin-users-outline-danger"
                                                :disabled="processing"
                                                type="submit"
                                            >
                                                {{ t('admin.suspend') }}
                                            </button>
                                        </Form>
                                        <Form
                                            v-else
                                            :action="`/admin/users/${user.id}/status`"
                                            method="patch"
                                            #default="{ processing }"
                                        >
                                            <input name="status" type="hidden" value="active" />
                                            <button
                                                class="nova-btn nova-admin-users-outline-btn"
                                                :disabled="processing"
                                                type="submit"
                                            >
                                                {{ t('admin.unsuspend') }}
                                            </button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="expandAdjustId === user.id" class="nova-admin-users-adjust-row">
                                <td colspan="5">
                                    <form class="nova-admin-adjust-inline" @submit.prevent="submitAdjustment(user.id)">
                                        <input
                                            v-model.number="adjustForm.delta"
                                            class="form-control"
                                            :max="maxAdjustment"
                                            :min="-maxAdjustment"
                                            required
                                            type="number"
                                        />
                                        <input
                                            v-model="adjustForm.reason"
                                            class="form-control"
                                            required
                                            :placeholder="t('admin.reasonRequired')"
                                        />
                                        <button
                                            class="nova-btn nova-btn-primary"
                                            :disabled="adjustForm.processing"
                                            type="submit"
                                        >
                                            {{ t('admin.applyAdjustment') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="users.links.length > 3" class="nova-admin-users-pagination">
            <a
                v-for="(link, index) in users.links"
                :key="index"
                :class="{ 'nova-muted': !link.url, 'nova-badge': link.active }"
                :href="link.url ?? undefined"
                v-html="link.label"
            />
        </div>
    </div>
</template>
