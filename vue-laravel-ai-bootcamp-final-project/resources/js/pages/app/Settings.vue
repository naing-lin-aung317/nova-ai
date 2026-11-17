<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import FormErrors from '@/components/shell/FormErrors.vue';
import LanguageSwitcher from '@/components/shell/LanguageSwitcher.vue';
import PasswordField from '@/components/shell/PasswordField.vue';
import ThemeSwitcher from '@/components/shell/ThemeSwitcher.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AuthUser } from '@/types/auth';
import { initialsFromName } from '@/utils/initials';

defineOptions({ layout: AppLayout });

const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser }).user,
);

const initials = computed(() => initialsFromName(user.value.name));

const showDeleteForm = ref(false);

function confirmDelete(event: SubmitEvent): void {
    if (! window.confirm(`${t('settings.deleteAccountConfirm')}?`)) {
        event.preventDefault();
    }
}
</script>

<template>
    <div class="nova-settings">
        <header class="nova-settings-header">
            <h1 class="nova-settings-title">{{ t('pages.settings') }}</h1>
            <p class="nova-settings-subtitle">{{ t('settings.pageSubtitle') }}</p>
        </header>

        <div class="nova-settings-layout">
            <div class="nova-settings-primary">
                <section class="nova-card nova-settings-card">
                    <div class="nova-settings-card-head">
                        <div class="nova-settings-avatar" aria-hidden="true">{{ initials }}</div>
                        <div class="nova-settings-card-head-text">
                            <h2 class="nova-settings-card-title">{{ t('settings.profile') }}</h2>
                            <p class="nova-settings-card-desc">{{ t('settings.profileSubtitle') }}</p>
                        </div>
                    </div>
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
                                <p class="nova-settings-field-hint">{{ t('settings.profileHint') }}</p>
                                <FormErrors :errors="errors.email" />
                            </div>
                        </div>
                        <div class="nova-settings-actions">
                            <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                                {{ t('settings.saveProfile') }}
                            </button>
                        </div>
                    </Form>
                </section>

                <section class="nova-card nova-settings-card">
                    <h2 class="nova-settings-card-title nova-settings-card-title--solo">
                        {{ t('settings.passwordSection') }}
                    </h2>
                    <Form action="/user/password" method="put" #default="{ errors, processing }">
                        <div class="nova-settings-field">
                            <label class="nova-settings-label">{{ t('settings.currentPassword') }}</label>
                            <PasswordField
                                autocomplete="current-password"
                                inline-toggle
                                name="current_password"
                            />
                            <FormErrors :errors="errors.current_password" />
                        </div>

                        <div class="nova-grid nova-grid-2 nova-settings-password-grid">
                            <div class="nova-settings-field">
                                <label class="nova-settings-label">{{ t('auth.newPassword') }}</label>
                                <PasswordField autocomplete="new-password" inline-toggle name="password" />
                                <FormErrors :errors="errors.password" />
                            </div>
                            <div class="nova-settings-field">
                                <label class="nova-settings-label">{{ t('auth.confirmPassword') }}</label>
                                <PasswordField
                                    autocomplete="new-password"
                                    inline-toggle
                                    name="password_confirmation"
                                />
                            </div>
                        </div>

                        <div class="nova-settings-actions">
                            <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                                {{ t('settings.updatePassword') }}
                            </button>
                        </div>
                    </Form>
                </section>
            </div>

            <aside class="nova-settings-aside">
                <section class="nova-card nova-settings-card">
                    <h2 class="nova-settings-card-title nova-settings-card-title--solo">
                        {{ t('settings.preferences') }}
                    </h2>
                    <div class="nova-settings-pref-row">
                        <div class="nova-settings-pref-copy">
                            <span class="nova-settings-pref-label">{{ t('shell.theme') }}</span>
                            <p class="nova-settings-pref-hint">{{ t('settings.themeHint') }}</p>
                        </div>
                        <ThemeSwitcher />
                    </div>
                    <div class="nova-settings-pref-row">
                        <div class="nova-settings-pref-copy">
                            <span class="nova-settings-pref-label">{{ t('settings.interfaceLanguage') }}</span>
                            <p class="nova-settings-pref-hint">{{ t('settings.languageSubtitle') }}</p>
                        </div>
                        <LanguageSwitcher />
                    </div>
                </section>

                <section class="nova-card nova-settings-card is-danger">
                    <h2 class="nova-settings-card-title nova-settings-card-title--danger">
                        {{ t('settings.dangerTitle') }}
                    </h2>
                    <p class="nova-settings-danger-text">{{ t('settings.dangerText') }}</p>
                    <Form
                        action="/settings/account"
                        method="delete"
                        class="nova-settings-danger-form"
                        #default="{ errors, processing }"
                        @submit="confirmDelete"
                    >
                        <template v-if="!showDeleteForm">
                            <button
                                class="nova-btn nova-settings-danger-btn"
                                type="button"
                                @click="showDeleteForm = true"
                            >
                                {{ t('settings.deleteAccount') }}
                            </button>
                        </template>
                        <template v-else>
                            <div class="nova-settings-field">
                                <label class="nova-settings-label">{{ t('settings.currentPassword') }}</label>
                                <PasswordField autocomplete="current-password" inline-toggle name="password" />
                                <FormErrors :errors="errors.password" />
                            </div>
                            <button
                                class="nova-btn nova-settings-danger-btn is-solid"
                                :disabled="processing"
                                type="submit"
                            >
                                {{ t('settings.deleteAccountConfirm') }}
                            </button>
                        </template>
                    </Form>
                </section>
            </aside>
        </div>
    </div>
</template>
