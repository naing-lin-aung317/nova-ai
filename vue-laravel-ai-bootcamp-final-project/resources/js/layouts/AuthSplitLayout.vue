<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthTypingDemo from '@/components/shell/AuthTypingDemo.vue';

const { t } = useI18n();
const page = usePage();

const isRegister = computed(() => page.component === 'auth/Register');

const heroTitle = computed(() =>
    isRegister.value ? t('shell.heroRegister') : t('shell.heroLogin'),
);
const heroLead = computed(() =>
    isRegister.value ? t('shell.heroRegisterNote') : t('shell.landingSub'),
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
    <div class="nova-auth-split">
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
                <p v-if="isRegister" class="nova-credits-lg">
                    {{ t('shell.heroRegisterCredits') }}
                </p>
                <p class="nova-hero-lead">{{ heroLead }}</p>

                <AuthTypingDemo />

                <ul class="nova-hero-tags">
                    <li v-for="tagKey in heroTags" :key="tagKey">
                        <span class="nova-hero-tag">{{ t(tagKey) }}</span>
                    </li>
                </ul>
            </div>

            <p class="nova-hero-foot nova-muted">{{ t('shell.noCharge') }}</p>
        </aside>
        <div class="nova-auth-panel">
            <div class="nova-auth-panel-inner">
                <slot />
            </div>
        </div>
    </div>
</template>
