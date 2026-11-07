export type AuthUser = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    role: 'user' | 'super_admin';
    status: 'pending_verification' | 'active' | 'suspended';
    preferred_language: 'en' | 'my';
    theme: 'light' | 'dark';
};
