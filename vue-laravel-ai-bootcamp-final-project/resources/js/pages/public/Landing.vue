<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import LandingFeatureIcon from '@/components/landing/LandingFeatureIcon.vue';
import LandingLayout from '@/layouts/LandingLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: LandingLayout });

const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser | null }).user,
);

const contentTypes = [
    { title: 'landing.tiktok', desc: 'landing.tiktokDesc', icon: 'tiktok' },
    { title: 'landing.facebook', desc: 'landing.facebookDesc', icon: 'facebook' },
    { title: 'landing.marketing', desc: 'landing.marketingDesc', icon: 'marketing' },
    { title: 'landing.captions', desc: 'landing.captionsDesc', icon: 'caption' },
    { title: 'landing.blog', desc: 'landing.blogDesc', icon: 'blog' },
    { title: 'landing.customTones', desc: 'landing.customTonesDesc', icon: 'tones' },
] as const;

const packages = [
    { name: 'Starter', price: 10, credits: 20, highlight: false },
    { name: 'Creator', price: 30, credits: 75, highlight: false },
    { name: 'Agency', price: 100, credits: 300, highlight: true },
] as const;

const faqs = [
    { q: 'landing.faqCredit', a: 'landing.faqCreditAnswer' },
    { q: 'landing.faqFail', a: 'landing.faqFailAnswer' },
    { q: 'landing.faqBurmese', a: 'landing.faqBurmeseAnswer' },
] as const;

const checks = ['landing.check1', 'landing.check2', 'landing.check3'] as const;

function perCredit(price: number, credits: number): string {
    return t('shell.perCredit', { amount: `$${(price / credits).toFixed(2)}` });
}

function ctaHref(): string {
    return user.value ? '/dashboard' : '/register';
}
</script>

<template>
    <Head :title="t('appName')" />

    <section class="nova-landing-hero">
        <div class="nova-landing-container nova-landing-hero-grid">
            <div class="nova-landing-hero-copy">
                <span class="nova-landing-lang-badge">{{ t('landing.langBadge') }}</span>
                <h1 class="nova-landing-hero-title">
                    {{ t('landing.heroTitleBefore') }}
                    <span class="nova-landing-hero-accent">{{ t('landing.heroTitleHighlight') }}</span
                    >{{ t('landing.heroTitleAfter') }}
                </h1>
                <p class="nova-landing-hero-lead">{{ t('shell.landingSub') }}</p>
                <div class="nova-landing-hero-actions">
                    <Link class="nova-btn nova-btn-primary nova-landing-btn-lg" :href="ctaHref()">
                        {{ user ? t('shell.goToDashboard') : t('shell.startFree') }}
                    </Link>
                    <a class="nova-btn nova-landing-btn-outline nova-landing-btn-lg" href="#pricing">
                        {{ t('shell.seePricing') }}
                    </a>
                </div>
                <ul class="nova-landing-checks">
                    <li v-for="key in checks" :key="key">{{ t(key) }}</li>
                </ul>
            </div>

            <div class="nova-landing-hero-demo nova-card">
                <div class="nova-landing-demo-tabs" role="tablist" aria-label="Demo content types">
                    <span class="is-active">{{ t('landing.demoTabTiktok') }}</span>
                    <span>{{ t('landing.demoTabFacebook') }}</span>
                    <span>{{ t('landing.demoTabCaption') }}</span>
                </div>
                <p class="nova-landing-demo-meta">
                    <span class="nova-landing-demo-badge">{{ t('landing.demoBadge') }}</span>
                    {{ t('landing.demoMeta') }}
                </p>
                <div class="nova-landing-demo-tones">
                    <span class="is-active">{{ t('landing.toneFriendly') }}</span>
                    <span>{{ t('landing.toneProfessional') }}</span>
                    <span>{{ t('landing.tonePlayful') }}</span>
                </div>
                <div class="nova-landing-demo-output">
                    <pre>{{ t('landing.demoPreview') }}</pre>
                </div>
                <button class="nova-btn nova-landing-demo-copy" type="button">
                    {{ t('landing.demoCopy') }}
                </button>
            </div>
        </div>
    </section>

    <section id="features" class="nova-landing-section nova-landing-features-section">
        <div class="nova-landing-container">
            <h2 class="nova-landing-section-title">{{ t('shell.featuresTitle') }}</h2>
            <div class="nova-landing-features">
                <article v-for="item in contentTypes" :key="item.title" class="nova-landing-feature-card">
                    <div class="nova-landing-feature-icon">
                        <LandingFeatureIcon :name="item.icon" />
                    </div>
                    <h3 class="nova-landing-feature-title">{{ t(item.title) }}</h3>
                    <p class="nova-landing-feature-desc">{{ t(item.desc) }}</p>
                </article>
            </div>
        </div>
    </section>

    <section class="nova-landing-section nova-landing-steps-section">
        <div class="nova-landing-container">
            <div class="nova-landing-steps-card">
                <h2 class="nova-landing-steps-title">{{ t('landing.stepsTitle') }}</h2>
                <div class="nova-landing-steps">
                    <article>
                        <span class="nova-landing-step-num">1</span>
                        <h3 class="nova-landing-step-heading">{{ t('landing.step1Title') }}</h3>
                        <p class="nova-landing-step-desc">{{ t('landing.step1Desc') }}</p>
                    </article>
                    <article>
                        <span class="nova-landing-step-num">2</span>
                        <h3 class="nova-landing-step-heading">{{ t('landing.step2Title') }}</h3>
                        <p class="nova-landing-step-desc">{{ t('landing.step2Desc') }}</p>
                    </article>
                    <article>
                        <span class="nova-landing-step-num">3</span>
                        <h3 class="nova-landing-step-heading">{{ t('landing.step3Title') }}</h3>
                        <p class="nova-landing-step-desc">{{ t('landing.step3Desc') }}</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section id="pricing" class="nova-landing-pricing-band">
        <div class="nova-landing-container">
            <h2 class="nova-landing-pricing-title">{{ t('shell.pricingTitle') }}</h2>
            <p class="nova-landing-pricing-sub">{{ t('shell.pricingSub') }}</p>
            <div class="nova-landing-pricing-grid">
                <div
                    v-for="pkg in packages"
                    :key="pkg.name"
                    class="nova-landing-price-card"
                    :class="{ 'is-highlight': pkg.highlight }"
                >
                    <div class="nova-landing-price-top">
                        <span class="nova-landing-price-name">{{ pkg.name }}</span>
                        <span v-if="pkg.highlight" class="nova-landing-price-badge">{{ t('shell.bestValue') }}</span>
                    </div>
                    <div class="nova-landing-price-amount">${{ pkg.price }}</div>
                    <div class="nova-landing-price-credits">{{ pkg.credits }} {{ t('nav.credits').toLowerCase() }}</div>
                    <hr class="nova-landing-price-divider" />
                    <p class="nova-landing-price-per">{{ perCredit(pkg.price, pkg.credits) }}</p>
                    <Link
                        class="nova-btn w-100"
                        :class="pkg.highlight ? 'nova-landing-price-btn-inverse' : 'nova-landing-price-btn'"
                        :href="ctaHref()"
                    >
                        {{ t('shell.buyStripe') }}
                    </Link>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="nova-landing-section nova-landing-faq-section">
        <div class="nova-landing-container nova-landing-faq-wrap">
            <h2 class="nova-landing-section-title">{{ t('shell.faqTitle') }}</h2>
            <div class="nova-landing-faq-list">
                <details v-for="item in faqs" :key="item.q" class="nova-landing-faq-item" open>
                    <summary>{{ t(item.q) }}</summary>
                    <p>{{ t(item.a) }}</p>
                </details>
            </div>
        </div>
    </section>

    <section class="nova-landing-section">
        <div class="nova-landing-container">
            <div class="nova-landing-cta-card">
                <h2>{{ t('landing.ctaTitle') }}</h2>
                <p>{{ t('landing.ctaSub') }}</p>
                <Link class="nova-btn nova-landing-cta-btn" :href="ctaHref()">
                    {{ t('shell.startFree') }}
                </Link>
            </div>
        </div>
    </section>

    <footer class="nova-landing-footer">
        <div class="nova-landing-container nova-landing-footer-inner">
            <span>{{ t('shell.footerRights') }}</span>
            <span>
                {{ t('shell.footerTerms') }} · {{ t('shell.footerPrivacy') }}
            </span>
        </div>
    </footer>
</template>
