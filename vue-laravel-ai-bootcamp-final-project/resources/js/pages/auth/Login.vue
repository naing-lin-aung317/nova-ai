<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import FormErrors from '@/components/shell/FormErrors.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import AuthSplitLayout from '@/layouts/AuthSplitLayout.vue';

defineOptions({ layout: AuthSplitLayout });

defineProps<{
    canResetPassword?: boolean;
    status?: string | null;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthCard title-key="pages.login">
        <p v-if="status" class="nova-muted">{{ status }}</p>
        <Form action="/login" method="post" #default="{ errors, processing }">
            <label class="form-label" for="email">{{ t('auth.email') }}</label>
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
                autocomplete="current-password"
                name="password"
                :placeholder="t('auth.passwordHint')"
            />
            <FormErrors :errors="errors.password" />

            <p class="nova-row" style="margin-top: 8px; justify-content: space-between">
                <Link v-if="canResetPassword" href="/forgot-password">{{
                    t('pages.forgot')
                }}</Link>
            </p>

            <button
                class="nova-btn nova-btn-primary w-100"
                :disabled="processing"
                style="margin-top: 14px"
                type="submit"
            >
                {{ t('pages.login') }}
            </button>
        </Form>

        <p class="nova-muted" style="margin-top: 12px">
            {{ t('auth.newHere') }}
            <Link href="/register">{{ t('auth.createAccount') }}</Link>
        </p>
    </AuthCard>
</template>
