<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import FormErrors from '@/components/shell/FormErrors.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';

defineOptions({ layout: AuthSimpleLayout });

const { t } = useI18n();
</script>

<template>
    <AuthCard subtitle-key="auth.forgotSubtitle" title-key="pages.forgot">
        <Form action="/forgot-password" method="post" #default="{ errors, processing }">
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

            <button
                class="nova-btn nova-btn-primary w-100"
                :disabled="processing"
                style="margin-top: 14px"
                type="submit"
            >
                {{ t('auth.sendResetLink') }}
            </button>
        </Form>

        <p class="nova-muted" style="margin-top: 12px">
            <Link href="/login">{{ t('auth.backToLogin') }}</Link>
        </p>
    </AuthCard>
</template>
