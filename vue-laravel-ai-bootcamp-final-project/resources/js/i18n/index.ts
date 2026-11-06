import { createI18n } from 'vue-i18n';
import { en } from '@/i18n/en';
import { my } from '@/i18n/my';

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en, my },
});
