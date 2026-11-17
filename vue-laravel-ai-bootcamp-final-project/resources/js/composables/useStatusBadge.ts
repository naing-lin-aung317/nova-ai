import { useI18n } from 'vue-i18n';

export type StatusBadgeTone = 'neutral' | 'ok' | 'warn' | 'danger';

export function useStatusBadge() {
    const { t } = useI18n();

    function paymentTone(status: string): StatusBadgeTone {
        if (status === 'paid') {
            return 'ok';
        }

        if (status === 'pending') {
            return 'warn';
        }

        if (status === 'refunded' || status === 'chargeback') {
            return 'danger';
        }

        return 'neutral';
    }

    function paymentLabel(status: string): string {
        const key = `billing.status.${status}`;

        return t(key) !== key ? t(key) : status;
    }

    function userStatusTone(status: string): StatusBadgeTone {
        if (status === 'active') {
            return 'ok';
        }

        if (status === 'suspended') {
            return 'danger';
        }

        if (status === 'pending_verification') {
            return 'warn';
        }

        return 'neutral';
    }

    function userStatusLabel(status: string): string {
        const key = `admin.userStatus.${status}`;

        return t(key) !== key ? t(key) : status;
    }

    return { paymentTone, paymentLabel, userStatusTone, userStatusLabel };
}
