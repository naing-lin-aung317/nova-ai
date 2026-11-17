<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
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
    <div class="nova-auth-login">
        <div class="nova-auth-login-top">
            <Link class="nova-auth-back" href="/">← {{ t('shell.backHome') }}</Link>
            <div class="nova-pref-toggles-row nova-pref-toggles-row--inline">
                <LanguageSwitcher />
                <ThemeSwitcher />
            </div>
        </div>

        <header class="nova-auth-login-head">
            <h1 class="nova-auth-login-title">{{ t('pages.register') }}</h1>
            <p class="nova-auth-login-sub">{{ t('auth.registerSubtitleShort') }}</p>
        </header>

        <Form action="/register" method="post" class="nova-auth-login-form" #default="{ errors, processing }">
            <input name="preferred_language" type="hidden" :value="locale.locale" />
            <input name="theme" type="hidden" :value="theme.preference" />
            <input name="terms" type="hidden" value="1" />
            <input name="password_confirmation" type="hidden" :value="password" />

            <div class="nova-auth-field">
                <label class="nova-auth-label" for="name">{{ t('auth.name') }}</label>
                <input
                    id="name"
                    class="form-control nova-auth-input"
                    name="name"
                    :placeholder="t('auth.namePlaceholder')"
                    required
                    type="text"
                />
                <FormErrors :errors="errors.name" />
            </div>

            <div class="nova-auth-field">
                <label class="nova-auth-label" for="email">{{ t('auth.email') }}</label>
                <input
                    id="email"
                    autocomplete="username"
                    class="form-control nova-auth-input"
                    name="email"
                    placeholder="you@example.com"
                    required
                    type="email"
                />
                <FormErrors :errors="errors.email" />
            </div>

            <div class="nova-auth-field">
                <div class="nova-auth-label-row">
                    <label class="nova-auth-label" for="register-password">{{ t('auth.password') }}</label>
                    <span class="nova-auth-field-hint">{{ t('auth.passwordMinNote') }}</span>
                </div>
                <PasswordField
                    id="register-password"
                    v-model="password"
                    autocomplete="new-password"
                    inline-toggle
                    name="password"
                    :placeholder="t('auth.passwordHint')"
                />
                <FormErrors :errors="errors.password" />
                <FormErrors :errors="errors.terms" />
            </div>

            <button
                class="nova-btn nova-btn-primary nova-auth-submit"
                :disabled="processing"
                type="submit"
            >
                {{ t('auth.createAccountSubmit') }}
            </button>
        </Form>

        <p class="nova-auth-login-footer">
            {{ t('auth.haveAccount') }}
            <Link href="/login">{{ t('pages.login') }}</Link>
        </p>
    </div>
</template>
