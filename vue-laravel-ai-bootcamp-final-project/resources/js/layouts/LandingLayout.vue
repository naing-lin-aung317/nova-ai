<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import { useThemeStore } from '@/stores/theme';
import type { AuthUser } from '@/types/auth';
import { storeToRefs } from 'pinia';

const { t } = useI18n();
const page = usePage();
const { preference: themePreference } = storeToRefs(useThemeStore());

const user = computed(
    () => (page.props.auth as { user: AuthUser | null }).user,
);
</script>

<template>
    <div class="nova-landing" :class="{ 'is-dark': themePreference === 'dark' }">
        <header class="nova-landing-header">
            <div class="nova-landing-header-inner">
                <Link class="nova-logo nova-landing-logo" href="/">
                    <i>N</i>
                    {{ t('appName') }}
                </Link>

                <nav class="nova-landing-nav" aria-label="Primary">
                    <a href="#features">{{ t('landing.navFeatures') }}</a>
                    <a href="#pricing">{{ t('landing.navPricing') }}</a>
                    <a href="#faq">{{ t('landing.navFaq') }}</a>
                </nav>

                <div class="nova-landing-header-end">
                    <div class="nova-pref-toggles-row nova-pref-toggles-row--inline nova-landing-prefs">
                        <LanguageSwitcher />
                        <ThemeSwitcher />
                    </div>
                    <template v-if="user">
                        <Link class="nova-btn nova-btn-primary nova-landing-header-cta" href="/dashboard">
                            {{ t('shell.goToDashboard') }}
                        </Link>
                    </template>
                    <template v-else>
                        <Link class="nova-landing-login" href="/login">{{ t('nav.login') }}</Link>
                        <Link class="nova-btn nova-btn-primary nova-landing-header-cta" href="/register">
                            {{ t('shell.startFree') }}
                        </Link>
                    </template>
                </div>
            </div>
        </header>
        <main class="nova-landing-main">
            <slot />
        </main>
    </div>
</template>
