<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import AdminPageHeader from '@/components/admin/AdminPageHeader.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps<{
    settings: Record<string, string | number>;
    flags: {
        gemini_key_configured: boolean;
        gemini_model_configured: boolean;
        stripe_fake: boolean;
    };
    diagnostics: {
        since: string;
        totals: Record<string, number>;
        last_failure: { error_code: string; occurred_at: string; duration_ms: number } | null;
    };
}>();

const DIAG_LABELS: Record<string, string> = {
    region_restricted: 'admin.aiDiagRegion',
    invalid_api_key: 'admin.aiDiagInvalidKey',
    unsupported_model: 'admin.aiDiagUnsupportedModel',
    missing_api_key: 'admin.aiDiagMissingKey',
    missing_model: 'admin.aiDiagMissingModel',
    provider_error: 'admin.aiDiagProvider',
    generation_failed: 'admin.aiDiagGenerationFailed',
    timeout: 'admin.aiDiagTimeout',
    stopped: 'admin.aiDiagStopped',
    server_error: 'admin.aiDiagGenerationFailed',
};

function diagLabel(code: string): string {
    return t(DIAG_LABELS[code] ?? 'admin.aiDiagProvider');
}

const diagnosticRows = Object.entries(props.diagnostics.totals).sort((a, b) => b[1] - a[1]);

const { t } = useI18n();

const defaults = {
    gemini: {
        model: String(props.settings['gemini.model'] ?? ''),
        request_timeout_seconds: Number(props.settings['gemini.request_timeout_seconds'] ?? 120),
        reservation_timeout_minutes: Number(props.settings['gemini.reservation_timeout_minutes'] ?? 5),
    },
    generation: {
        max_concurrent_per_user: Number(props.settings['generation.max_concurrent_per_user'] ?? 1),
    },
};

const form = useForm(defaults);

function resetForm(): void {
    form.defaults(defaults);
    form.reset();
}

const stripeTestMode = props.flags.stripe_fake;

const geminiReady =
    props.flags.gemini_key_configured && props.flags.gemini_model_configured;
</script>

<template>
    <Head :title="t('pages.ai')" />

    <AdminPageHeader :subtitle="t('admin.aiSubtitle')" :title="t('pages.ai')" />

    <div class="nova-grid nova-grid-2 nova-admin-split" style="margin-top: 12px">
        <form class="nova-card" @submit.prevent="form.patch('/admin/ai')">
            <p class="nova-muted" style="margin: 0 0 12px">{{ t('admin.geminiConnectIntro') }}</p>
            <label class="form-label">{{ t('admin.geminiModel') }}</label>
            <input v-model="form.gemini.model" class="form-control" required />
            <p class="nova-muted" style="margin: 6px 0 0">{{ t('admin.geminiModelHint') }}</p>

            <div class="nova-grid nova-grid-2" style="margin-top: 14px">
                <div>
                    <label class="form-label">{{ t('admin.requestTimeout') }}</label>
                    <input v-model.number="form.gemini.request_timeout_seconds" class="form-control" required type="number" />
                    <p class="nova-muted" style="margin: 4px 0 0">{{ t('admin.requestTimeoutHint') }}</p>
                </div>
                <div>
                    <label class="form-label">{{ t('admin.reservationTimeout') }}</label>
                    <input
                        v-model.number="form.gemini.reservation_timeout_minutes"
                        class="form-control"
                        required
                        type="number"
                    />
                    <p class="nova-muted" style="margin: 4px 0 0">{{ t('admin.reservationTimeoutHint') }}</p>
                </div>
            </div>

            <label class="form-label" style="margin-top: 14px">{{ t('admin.maxConcurrent') }}</label>
            <input v-model.number="form.generation.max_concurrent_per_user" class="form-control" required type="number" />

            <div class="nova-admin-edit-actions" style="margin-top: 16px">
                <button class="nova-btn nova-btn-primary" :disabled="form.processing" type="submit">
                    {{ t('settings.saveProfile') }}
                </button>
                <button class="nova-btn" type="button" @click="resetForm">{{ t('admin.resetForm') }}</button>
            </div>
        </form>

        <div class="nova-card">
            <h3 style="margin: 0 0 4px">{{ t('admin.environmentTitle') }}</h3>
            <p class="nova-muted" style="margin: 0 0 12px">{{ t('admin.environmentHint') }}</p>
            <p v-if="stripeTestMode" class="nova-alert nova-admin-test-alert">{{ t('admin.testModeAlert') }}</p>
            <div class="nova-admin-env-row">
                <span>{{ t('admin.aiBackend') }}</span>
                <span class="nova-badge">gemini</span>
            </div>
            <p class="nova-muted" style="margin: 10px 0 8px">{{ t('admin.geminiConnectChecklist') }}</p>
            <div class="nova-admin-env-row">
                <span>GEMINI_API_KEY</span>
                <span class="nova-badge" :class="flags.gemini_key_configured ? 'is-ok' : 'is-warn'">
                    {{ flags.gemini_key_configured ? t('admin.envConfigured') : t('admin.envMissing') }}
                </span>
            </div>
            <div class="nova-admin-env-row">
                <span>GEMINI_MODEL</span>
                <span class="nova-badge" :class="flags.gemini_model_configured ? 'is-ok' : 'is-warn'">
                    {{ flags.gemini_model_configured ? t('admin.envConfigured') : t('admin.envMissing') }}
                </span>
            </div>
            <p v-if="! geminiReady" class="nova-muted" style="margin: 8px 0 0">
                {{ t('admin.geminiConnectMissing') }}
            </p>
            <div class="nova-admin-env-row">
                <span>STRIPE_FAKE</span>
                <span class="nova-badge" :class="flags.stripe_fake ? 'is-warn' : 'is-ok'">
                    {{ flags.stripe_fake ? 'true' : 'false' }}
                </span>
            </div>

            <h3 style="margin: 16px 0 4px">{{ t('admin.aiDiagnosticsTitle') }}</h3>
            <p class="nova-muted" style="margin: 0 0 10px">{{ t('admin.aiDiagnosticsHint') }}</p>
            <p v-if="diagnosticRows.length === 0" class="nova-muted" style="margin: 0">
                {{ t('admin.aiDiagnosticsNone') }}
            </p>
            <template v-else>
                <div
                    v-for="[code, count] in diagnosticRows"
                    :key="code"
                    class="nova-admin-env-row"
                >
                    <span>{{ diagLabel(code) }}</span>
                    <span class="nova-badge">{{ count }}</span>
                </div>
            </template>
            <p v-if="diagnostics.last_failure" class="nova-muted" style="margin: 10px 0 0">
                {{ t('admin.aiDiagnosticsLast') }}:
                {{ diagLabel(diagnostics.last_failure.error_code) }}
                · {{ diagnostics.last_failure.duration_ms }}ms
            </p>
        </div>
    </div>
</template>
