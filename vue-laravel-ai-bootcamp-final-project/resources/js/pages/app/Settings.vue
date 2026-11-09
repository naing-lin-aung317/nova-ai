<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: AppLayout });

const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser }).user,
);

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(`${t('settings.deleteAccount')}?`)) {
        event.preventDefault();
    }
}
</script>

<template>
    <div class="nova-settings">
        <header class="nova-settings-header">
            <h2 class="nova-settings-title">{{ t('pages.settings') }}</h2>
            <p class="nova-muted" style="margin-top: 8px">{{ t('settings.pageSubtitle') }}</p>
        </header>

        <div class="nova-settings-stack">
            <section class="nova-card nova-settings-card">
                <h3>{{ t('settings.profile') }}</h3>
                <Form
                    action="/user/profile-information"
                    method="put"
                    #default="{ errors, processing }"
                >
                    <div class="nova-grid nova-grid-2 nova-settings-profile-grid">
                        <div class="nova-settings-field">
                            <label class="nova-settings-label" for="name">{{ t('auth.name') }}</label>
                            <input
                                id="name"
                                class="form-control"
                                name="name"
                                required
                                type="text"
                                :value="user.name"
                            />
                            <FormErrors :errors="errors.name" />
                        </div>
                        <div class="nova-settings-field">
                            <label class="nova-settings-label" for="email">{{ t('auth.email') }}</label>
                            <input
                                id="email"
                                class="form-control"
                                name="email"
                                required
                                type="email"
                                :value="user.email"
                            />
                            <FormErrors :errors="errors.email" />
                        </div>
                    </div>
                    <p class="nova-muted nova-settings-hint">{{ t('settings.profileHint') }}</p>
                    <div class="nova-settings-actions">
                        <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                            {{ t('settings.saveProfile') }}
                        </button>
                    </div>
                </Form>
            </section>

            <section class="nova-card nova-settings-card">
                <div class="nova-settings-row-head">
                    <h3>{{ t('shell.theme') }}</h3>
                    <ThemeSwitcher />
                </div>
                <p class="nova-muted nova-settings-hint">{{ t('settings.themeHint') }}</p>
            </section>

            <section class="nova-card nova-settings-card">
                <div class="nova-settings-row-head">
                    <h3>{{ t('shell.language') }}</h3>
                    <LanguageSwitcher />
                </div>
                <p class="nova-muted nova-settings-hint">{{ t('settings.languageHint') }}</p>
            </section>

            <section class="nova-card nova-settings-card">
                <h3>{{ t('settings.passwordSection') }}</h3>
                <Form action="/user/password" method="put" #default="{ errors, processing }">
                    <div class="nova-settings-field">
                        <label class="nova-settings-label">{{ t('settings.currentPassword') }}</label>
                        <PasswordField autocomplete="current-password" name="current_password" />
                        <FormErrors :errors="errors.current_password" />
                    </div>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label">{{ t('auth.newPassword') }}</label>
                        <PasswordField autocomplete="new-password" name="password" />
                        <FormErrors :errors="errors.password" />
                    </div>

                    <div class="nova-settings-field">
                        <label class="nova-settings-label">{{ t('auth.confirmPassword') }}</label>
                        <PasswordField autocomplete="new-password" name="password_confirmation" />
                    </div>

                    <div class="nova-settings-actions">
                        <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                            {{ t('settings.updatePassword') }}
                        </button>
                    </div>
                </Form>
            </section>

            <section class="nova-card nova-settings-card is-danger">
                <h3>{{ t('settings.dangerTitle') }}</h3>
                <p class="nova-muted nova-settings-hint">{{ t('settings.dangerText') }}</p>
                <Form
                    action="/settings/account"
                    method="delete"
                    #default="{ errors, processing }"
                    @submit="confirmDelete"
                >
                    <div class="nova-settings-field">
                        <label class="nova-settings-label">{{ t('settings.currentPassword') }}</label>
                        <PasswordField autocomplete="current-password" name="password" />
                        <FormErrors :errors="errors.password" />
                    </div>
                    <div class="nova-settings-actions">
                        <button
                            class="nova-btn nova-settings-danger-btn"
                            :disabled="processing"
                            type="submit"
                        >
                            {{ t('settings.deleteAccount') }}
                        </button>
                    </div>
                </Form>
            </section>
        </div>
    </div>
</template>
