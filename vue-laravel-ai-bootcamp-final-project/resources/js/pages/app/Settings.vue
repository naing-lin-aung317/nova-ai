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
    <div>
        <h2>{{ t('pages.settings') }}</h2>

        <div style="max-width: 640px">
            <div class="nova-card">
                <h3>{{ t('settings.profile') }}</h3>
                <Form
                    action="/user/profile-information"
                    method="put"
                    #default="{ errors, processing }"
                >
                    <div class="nova-grid nova-grid-2">
                        <div>
                            <label class="form-label" for="name">{{ t('auth.name') }}</label>
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
                        <div>
                            <label class="form-label" for="email">{{ t('auth.email') }}</label>
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
                    <p class="nova-muted">{{ t('settings.profileHint') }}</p>
                    <button class="nova-btn nova-btn-primary" :disabled="processing" type="submit">
                        {{ t('settings.saveProfile') }}
                    </button>
                </Form>
            </div>

            <div class="nova-card">
                <div class="nova-row" style="justify-content: space-between">
                    <h3>{{ t('shell.theme') }}</h3>
                    <ThemeSwitcher />
                </div>
                <p class="nova-muted">{{ t('settings.themeHint') }}</p>
            </div>

            <div class="nova-card">
                <div class="nova-row" style="justify-content: space-between">
                    <h3>{{ t('shell.language') }}</h3>
                    <LanguageSwitcher />
                </div>
                <p class="nova-muted">{{ t('settings.languageHint') }}</p>
            </div>

            <div class="nova-card">
                <h3>{{ t('settings.passwordSection') }}</h3>
                <Form action="/user/password" method="put" #default="{ errors, processing }">
                    <label class="form-label">{{ t('settings.currentPassword') }}</label>
                    <PasswordField autocomplete="current-password" name="current_password" />
                    <FormErrors :errors="errors.current_password" />

                    <label class="form-label" style="margin-top: 12px">{{
                        t('auth.newPassword')
                    }}</label>
                    <PasswordField autocomplete="new-password" name="password" />
                    <FormErrors :errors="errors.password" />

                    <label class="form-label" style="margin-top: 12px">{{
                        t('auth.confirmPassword')
                    }}</label>
                    <PasswordField autocomplete="new-password" name="password_confirmation" />

                    <br />
                    <button class="nova-btn" :disabled="processing" type="submit">
                        {{ t('settings.updatePassword') }}
                    </button>
                </Form>
            </div>

            <div class="nova-card" style="border-color: #b91c1c">
                <h3 style="color: #b91c1c">{{ t('settings.dangerTitle') }}</h3>
                <p class="nova-muted">{{ t('settings.dangerText') }}</p>
                <Form
                    action="/settings/account"
                    method="delete"
                    #default="{ errors, processing }"
                    @submit="confirmDelete"
                >
                    <PasswordField autocomplete="current-password" name="password" />
                    <FormErrors :errors="errors.password" />
                    <br />
                    <button class="nova-btn" :disabled="processing" style="border-color: #b91c1c" type="submit">
                        {{ t('settings.deleteAccount') }}
                    </button>
                </Form>
            </div>
        </div>
    </div>
</template>
