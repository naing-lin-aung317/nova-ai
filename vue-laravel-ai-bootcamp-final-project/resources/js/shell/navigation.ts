export interface NavItem {
    to: string;
    label: string;
}

export const userNav: NavItem[] = [
    { to: '/dashboard', label: 'nav.dashboard' },
    { to: '/generator', label: 'nav.generator' },
    { to: '/tones', label: 'nav.tones' },
    { to: '/history', label: 'nav.history' },
    { to: '/billing', label: 'nav.billing' },
    { to: '/settings', label: 'nav.settings' },
];

export const adminNav: NavItem[] = [
    { to: '/admin', label: 'nav.overview' },
    { to: '/admin/users', label: 'nav.users' },
    { to: '/admin/packages', label: 'nav.packages' },
    { to: '/admin/tones', label: 'nav.builtInTones' },
    { to: '/admin/payments', label: 'nav.payments' },
    { to: '/admin/monitoring', label: 'nav.monitoring' },
    { to: '/admin/audit', label: 'nav.audit' },
    { to: '/admin/ai', label: 'nav.ai' },
];
