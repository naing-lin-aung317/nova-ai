<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import FormErrors from '@/components/shell/FormErrors.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';

defineOptions({ layout: AuthSimpleLayout });

defineProps<{
    email: string;
    token: string;
}>();

const { t } = useI18n();
</script>

<template>
    <AuthCard title-key="pages.reset">
        <Form action="/reset-password" method="post" #default="{ errors, processing }">
            <input name="token" type="hidden" :value="token" />
            <input name="email" type="hidden" :value="email" />

            <label class="form-label" for="password">{{ t('auth.newPassword') }}</label>
            <PasswordField
                autocomplete="new-password"
                name="password"
                :placeholder="t('auth.passwordHint')"
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

            <button
                class="nova-btn nova-btn-primary w-100"
                :disabled="processing"
                style="margin-top: 14px"
                type="submit"
            >
                {{ t('auth.updatePassword') }}
            </button>
        </Form>

        <p class="nova-muted" style="margin-top: 12px">
            <Link href="/login">{{ t('auth.backToLogin') }}</Link>
        </p>
    </AuthCard>
</template>
