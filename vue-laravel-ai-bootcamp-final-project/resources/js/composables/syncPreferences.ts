import { router, usePage } from '@inertiajs/vue3';
import type { AppLocale } from '@/stores/locale';
import type { ThemePreference } from '@/stores/theme';
import type { AuthUser } from '@/types/auth';

function authUser(): AuthUser | null {
    return (usePage().props.auth as { user: AuthUser | null } | undefined)?.user ?? null;
}

export function persistTheme(theme: ThemePreference): void {
    if (! authUser()) {
        return;
    }

    router.patch(
        '/settings/preferences',
        { theme },
        { preserveScroll: true, preserveState: true, only: ['auth'] },
    );
}

export function persistLocale(preferred_language: AppLocale): void {
    if (! authUser()) {
        return;
    }

    router.patch(
        '/settings/preferences',
        { preferred_language },
        { preserveScroll: true, preserveState: true, only: ['auth'] },
    );
}
