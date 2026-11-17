<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthPreviewCard from '@/components/shell/AuthPreviewCard.vue';
import AuthTypingDemo from '@/components/shell/AuthTypingDemo.vue';

const { t } = useI18n();
const page = usePage();

const isRegister = computed(() => page.component === 'auth/Register');
const isLogin = computed(() => page.component === 'auth/Login');

const usesAuthPreview = computed(() => isLogin.value || isRegister.value);

const heroTitle = computed(() => {
    if (isLogin.value) {
        return t('auth.splitHeroTitle');
    }

    if (isRegister.value) {
        return t('auth.registerHeroTitle');
    }

    return t('shell.heroLogin');
});

const heroLead = computed(() => {
    if (usesAuthPreview.value) {
        return t('auth.splitHeroLead');
    }

    return t('shell.landingSub');
});

const heroFoot = computed(() =>
    usesAuthPreview.value ? t('auth.loginFoot') : t('shell.noCharge'),
);

const heroTags = [
    'landing.tiktok',
    'landing.facebook',
    'landing.captions',
    'landing.marketing',
    'landing.blog',
] as const;
</script>

<template>
    <div
        class="nova-auth-split"
        :class="{ 'is-login': isLogin, 'is-register': isRegister }"
    >
        <aside class="nova-hero">
            <div class="nova-auth-circles" aria-hidden="true">
                <span class="nova-auth-circle nova-auth-circle--1" />
                <span class="nova-auth-circle nova-auth-circle--2" />
                <span class="nova-auth-circle nova-auth-circle--3" />
            </div>

            <Link class="nova-logo" href="/">
                <i>N</i>{{ t('appName') }}
            </Link>

            <div class="nova-hero-body">
                <h2>{{ heroTitle }}</h2>
                <p class="nova-hero-lead">{{ heroLead }}</p>

                <AuthPreviewCard v-if="usesAuthPreview" />
                <AuthTypingDemo v-else-if="!isRegister" />

                <ul v-if="!usesAuthPreview" class="nova-hero-tags">
                    <li v-for="tagKey in heroTags" :key="tagKey">
                        <span class="nova-hero-tag">{{ t(tagKey) }}</span>
                    </li>
                </ul>
            </div>

            <p class="nova-hero-foot nova-muted">{{ heroFoot }}</p>
        </aside>
        <div class="nova-auth-panel">
            <div class="nova-auth-panel-inner">
                <slot />
            </div>
        </div>
    </div>
</template>
