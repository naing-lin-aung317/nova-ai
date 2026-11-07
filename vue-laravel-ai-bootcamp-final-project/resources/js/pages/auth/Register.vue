<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import FormErrors from '@/components/shell/FormErrors.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import AuthSplitLayout from '@/layouts/AuthSplitLayout.vue';
import { useLocaleStore } from '@/stores/locale';
import { useThemeStore } from '@/stores/theme';

defineOptions({ layout: AuthSplitLayout });

const { t } = useI18n();
const locale = useLocaleStore();
const theme = useThemeStore();
const password = ref('');
</script>

<template>
    <AuthCard subtitle-key="auth.registerSubtitle" title-key="pages.register">
        <Form action="/register" method="post" #default="{ errors, processing }">
            <input name="preferred_language" type="hidden" :value="locale.locale" />
            <input name="theme" type="hidden" :value="theme.preference" />

            <label class="form-label" for="name">{{ t('auth.name') }}</label>
            <input
                id="name"
                class="form-control"
                name="name"
                :placeholder="t('auth.namePlaceholder')"
                required
                type="text"
            />
            <FormErrors :errors="errors.name" />

            <label class="form-label" for="email" style="margin-top: 12px">{{
                t('auth.email')
            }}</label>
            <input
                id="email"
                autocomplete="username"
                class="form-control"
                name="email"
                placeholder="you@example.com"
                required
                type="email"
            />
            <FormErrors :errors="errors.email" />

            <label class="form-label" for="password" style="margin-top: 12px">{{
                t('auth.password')
            }}</label>
            <PasswordField
                v-model="password"
                autocomplete="new-password"
                name="password"
                :placeholder="t('auth.passwordHint')"
                show-strength
            />
            <FormErrors :errors="errors.password" />

            <label class="form-label" for="password_confirmation" style="margin-top: 12px">{{
                t('auth.confirmPassword')
            }}</label>
            <PasswordField
                autocomplete="new-password"
                name="password_confirmation"
                :placeholder="t('auth.passwordHint')"
            />

            <label class="nova-muted" style="display: flex; gap: 8px; margin-top: 14px">
                <input name="terms" required style="width: auto" type="checkbox" value="1" />
                <span>{{ t('auth.agreeTerms') }}</span>
            </label>
            <FormErrors :errors="errors.terms" />

            <button
                class="nova-btn nova-btn-primary w-100"
                :disabled="processing"
                style="margin-top: 14px"
                type="submit"
            >
                {{ t('pages.register') }}
            </button>
        </Form>

        <p class="nova-muted" style="margin-top: 12px">
            {{ t('auth.haveAccount') }}
            <Link href="/login">{{ t('pages.login') }}</Link>
        </p>
    </AuthCard>
</template>
