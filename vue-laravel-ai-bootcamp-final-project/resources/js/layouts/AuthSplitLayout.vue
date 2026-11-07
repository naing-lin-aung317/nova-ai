<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();
const page = usePage();

const isRegister = computed(() => page.component === 'auth/Register');
const heroTitle = computed(() =>
    isRegister.value ? t('shell.heroRegister') : t('shell.heroLogin'),
);
const heroLead = computed(() =>
    isRegister.value ? t('shell.heroRegisterNote') : t('shell.landingSub'),
);
</script>

<template>
    <div class="nova-auth-split">
        <aside class="nova-hero">
            <Link class="nova-logo" href="/">
                <i>N</i>{{ t('appName') }}
            </Link>
            <div>
                <h2>{{ heroTitle }}</h2>
                <p v-if="isRegister" class="nova-credits-lg">{{ t('shell.heroRegisterCredits') }}</p>
                <p>{{ heroLead }}</p>
            </div>
            <p>{{ t('shell.noCharge') }}</p>
        </aside>
        <div class="nova-auth-panel">
            <slot />
        </div>
    </div>
</template>
