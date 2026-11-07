<script setup lang="ts">
import { Form, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import AuthCard from '@/components/shell/AuthCard.vue';
import AuthSimpleLayout from '@/layouts/AuthSimpleLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: AuthSimpleLayout });

defineProps<{
    status?: string | null;
}>();

const { t } = useI18n();
const page = usePage();

const email = computed(
    () => (page.props.auth as { user: AuthUser | null }).user?.email ?? '',
);
</script>

<template>
    <AuthCard subtitle-key="auth.verifySubtitle" title-key="pages.verify">
        <p class="nova-muted">
            {{ t('auth.verifySent', { email }) }}
        </p>
        <p v-if="status" class="nova-muted">{{ status }}</p>

        <Form action="/email/verification-notification" method="post" #default="{ processing }">
            <button
                class="nova-btn nova-btn-primary w-100"
                :disabled="processing"
                type="submit"
            >
                {{ t('auth.resendVerification') }}
            </button>
        </Form>

        <p class="nova-muted" style="margin-top: 12px; text-align: center">
            <Link href="/dashboard">{{ t('auth.continueToApp') }}</Link>
        </p>
    </AuthCard>
</template>
