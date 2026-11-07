<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import type { AuthUser } from '@/types/auth';

const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser | null }).user,
);
</script>

<template>
    <div class="nova-public">
        <header class="nova-row" style="padding: 8px 0 30px">
            <Link class="nova-logo" href="/">
                <i>N</i>{{ t('appName') }}
            </Link>
            <span class="nova-row">
                <LanguageSwitcher />
                <ThemeSwitcher />
                <template v-if="user">
                    <Link class="nova-btn nova-btn-primary" href="/dashboard">
                        {{ t('shell.goToDashboard') }}
                    </Link>
                </template>
                <template v-else>
                    <Link class="nova-btn" href="/login">{{ t('nav.login') }}</Link>
                    <Link class="nova-btn nova-btn-primary" href="/register">
                        {{ t('shell.startFree') }}
                    </Link>
                </template>
            </span>
        </header>
        <slot />
    </div>
</template>
