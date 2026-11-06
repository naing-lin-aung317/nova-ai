<script setup lang="ts">
import { useI18n } from 'vue-i18n';
import { useLocaleStore, type AppLocale } from '@/stores/locale';

const { t } = useI18n();
const locale = useLocaleStore();

const options: { id: AppLocale; label: string }[] = [
    { id: 'en', label: 'EN' },
    { id: 'my', label: 'မြန်မာ' },
];
</script>

<template>
    <div>
        <div class="nova-seg" role="group" :aria-label="t('shell.language')">
            <button
                v-for="option in options"
                :key="option.id"
                type="button"
                :aria-pressed="locale.locale === option.id"
                @click="locale.apply(option.id)"
            >
                {{ option.label }}
            </button>
        </div>
    </div>
</template>
