<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import AuthSplitLayout from '@/layouts/AuthSplitLayout.vue';

defineOptions({ layout: AuthSplitLayout });

defineProps<{
    canResetPassword?: boolean;
    status?: string | null;
}>();

const { t } = useI18n();
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
            <h1 class="nova-auth-login-title">{{ t('auth.welcomeBack') }}</h1>
            <p class="nova-auth-login-sub">{{ t('auth.loginSubtitle') }}</p>
        </header>

        <p v-if="status" class="nova-muted nova-auth-login-status">{{ status }}</p>

        <Form action="/login" method="post" class="nova-auth-login-form" #default="{ errors, processing }">
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
                    <label class="nova-auth-label" for="login-password">{{ t('auth.password') }}</label>
                    <Link v-if="canResetPassword" class="nova-auth-forgot" href="/forgot-password">
                        {{ t('pages.forgot') }}
                    </Link>
                </div>
                <PasswordField
                    autocomplete="current-password"
                    id="login-password"
                    inline-toggle
                    name="password"
                    :placeholder="t('auth.passwordHint')"
                />
                <FormErrors :errors="errors.password" />
            </div>

            <button
                class="nova-btn nova-btn-primary nova-auth-submit"
                :disabled="processing"
                type="submit"
            >
                {{ t('pages.login') }}
            </button>
        </Form>

        <p class="nova-auth-login-footer">
            {{ t('auth.newHere') }}
            <Link href="/register">{{ t('auth.createAccount') }}</Link>
        </p>
    </div>
</template>
