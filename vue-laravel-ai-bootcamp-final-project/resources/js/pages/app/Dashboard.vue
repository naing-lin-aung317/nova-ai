<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: AppLayout });

type DashboardStats = {
    availableCredits: number;
    generationsThisMonth: number;
    creditsSpentThisMonth: number;
    generationsTotal: number;
    customTones: number;
};

type RecentContentRow = {
    id: number;
    content_type: string;
    topic: string;
    language: string;
    created_at: string | null;
    url: string;
};

const props = defineProps<{
    stats: DashboardStats;
    contentTypes: string[];
    recentContent: RecentContentRow[];
    lowCredits: boolean;
}>();

const { t } = useI18n();
const page = usePage();

const authUser = computed(() => (page.props.auth as { user: AuthUser }).user);

const userName = computed(() => authUser.value.name);

const isSuperAdmin = computed(() => authUser.value.role === 'super_admin');

const quickType = ref(props.contentTypes[0] ?? 'facebook_post');
const quickTopic = ref('');

const generatorLink = computed(() => {
    const params = new URLSearchParams();
    params.set('content_type', quickType.value);

    if (quickTopic.value.trim()) {
        params.set('topic', quickTopic.value.trim());
    }

    return `/generator?${params.toString()}`;
});
</script>

<template>
    <div class="nova-dash">
        <section class="nova-card nova-dash-welcome">
            <p v-if="isSuperAdmin" class="nova-dash-admin-link">
                <Link class="nova-btn nova-btn-primary" href="/admin">{{ t('nav.adminPanel') }}</Link>
            </p>
            <h2 class="nova-dash-welcome-title">
                {{ t('dashboard.welcomeBanner', { name: userName, count: stats.availableCredits }) }}
            </h2>
            <p v-if="lowCredits" class="nova-alert" style="margin-top: 12px; margin-bottom: 0">
                {{ t('dashboard.lowCredits', { count: stats.availableCredits }) }}
                <Link href="/billing">{{ t('dashboard.buyCredits') }}</Link>
            </p>
            <p v-else-if="stats.customTones === 0" class="nova-muted nova-dash-tip">
                {{ t('dashboard.toneTip') }}
            </p>
        </section>

        <div class="nova-dash-stats">
    <article class="nova-card nova-dash-stat">
        <span class="nova-dash-stat-label">{{ t('dashboard.creditBalance') }}</span>
        <p class="nova-dash-stat-value">{{ stats.availableCredits }}</p>
        <Link class="nova-dash-stat-link" href="/billing">{{ t('dashboard.buyMore') }}</Link>
    </article>
    <article class="nova-card nova-dash-stat">
        <span class="nova-dash-stat-label">{{ t('dashboard.generationsThisMonth') }}</span>
        <p class="nova-dash-stat-value">{{ stats.generationsThisMonth }}</p>
    </article>
    <article class="nova-card nova-dash-stat">
        <span class="nova-dash-stat-label">{{ t('dashboard.creditsSpentThisMonth') }}</span>
        <p class="nova-dash-stat-value">{{ stats.creditsSpentThisMonth }}</p>
    </article>
    <article class="nova-card nova-dash-stat">
        <span class="nova-dash-stat-label">{{ t('dashboard.generationsAllTime') }}</span>
        <p class="nova-dash-stat-value">{{ stats.generationsTotal }}</p>
    </article>
</div>

        <div class="nova-dash-split">
            <section class="nova-card nova-dash-panel">
                <h3 class="nova-dash-panel-title">{{ t('dashboard.quickGenerate') }}</h3>

                <p class="nova-settings-label" style="margin-top: 0">{{ t('dashboard.quickType') }}</p>
                <div class="nova-dash-chips" role="group">
                    <button
                        v-for="type in contentTypes"
                        :key="type"
                        type="button"
                        class="nova-dash-chip"
                        :class="{ 'is-active': quickType === type }"
                        @click="quickType = type"
                    >
                        {{ t(`generator.types.${type}`) }}
                    </button>
                </div>

                <div class="nova-settings-field" style="margin-top: 14px">
                    <label class="nova-settings-label" for="quick-topic">{{ t('dashboard.quickAbout') }}</label>
                    <textarea
                        id="quick-topic"
                        v-model="quickTopic"
                        class="form-control nova-gen-brief"
                        rows="4"
                        :placeholder="t('dashboard.quickPlaceholder')"
                    />
                </div>

                <div class="nova-dash-quick-foot">
                    <span class="nova-muted">{{ t('dashboard.costsOneCredit') }}</span>
                    <Link class="nova-btn nova-btn-primary" :href="generatorLink">
                        {{ t('dashboard.openInGenerator') }}
                    </Link>
                </div>
            </section>

            <section class="nova-card nova-dash-panel">
                <div class="nova-dash-panel-head">
                    <h3 class="nova-dash-panel-title">{{ t('dashboard.recentGenerations') }}</h3>
                    <Link class="nova-dash-stat-link" href="/history">{{ t('dashboard.viewAll') }}</Link>
                </div>

                <p v-if="recentContent.length === 0" class="nova-dash-empty">
                    {{ t('dashboard.startCreating') }}
                </p>
                <template v-else>
                    <div
                        v-for="item in recentContent"
                        :key="item.id"
                        class="nova-dash-recent-row"
                    >
                        <Link :href="item.url" class="nova-dash-recent-topic">{{ item.topic }}</Link>
                        <p class="nova-muted" style="margin: 4px 0 0; font-size: 13px">
                            {{ t(`generator.types.${item.content_type}`) }}
                            · {{ t(`generator.languages.${item.language}`) }}
                        </p>
                    </div>
                </template>
            </section>
        </div>
    </div>
</template>
