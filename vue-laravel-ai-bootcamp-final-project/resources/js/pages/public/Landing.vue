<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { AuthUser } from '@/types/auth';

defineOptions({ layout: PublicLayout });

const { t } = useI18n();
const page = usePage();

const user = computed(
    () => (page.props.auth as { user: AuthUser | null }).user,
);

const contentTypes = [
    { title: 'landing.tiktok', desc: 'landing.tiktokDesc' },
    { title: 'landing.facebook', desc: 'landing.facebookDesc' },
    { title: 'landing.marketing', desc: 'landing.marketingDesc' },
    { title: 'landing.captions', desc: 'landing.captionsDesc' },
    { title: 'landing.blog', desc: 'landing.blogDesc' },
    { title: 'landing.customTones', desc: 'landing.customTonesDesc' },
] as const;

const packages = [
    { name: 'Starter', price: 10, credits: 20, highlight: false },
    { name: 'Creator', price: 30, credits: 75, highlight: false },
    { name: 'Agency', price: 100, credits: 300, highlight: true },
] as const;

function perCredit(price: number, credits: number): string {
    return t('shell.perCredit', { amount: `$${(price / credits).toFixed(2)}` });
}
</script>

<template>
    <section>
        <div class="nova-grid nova-grid-2" style="align-items: center; padding: 8px 0 30px">
            <div>
                <h1>{{ t('pages.landing') }}</h1>
                <p class="nova-muted">{{ t('shell.landingSub') }}</p>
                <p>
                    <Link
                        v-if="user"
                        class="nova-btn nova-btn-primary"
                        href="/dashboard"
                    >
                        {{ t('shell.goToDashboard') }}
                    </Link>
                    <template v-else>
                        <Link class="nova-btn nova-btn-primary" href="/register">
                            {{ t('shell.startFree') }}
                        </Link>
                        <Link class="nova-btn" href="#pricing" style="margin-left: 8px">
                            {{ t('shell.seePricing') }}
                        </Link>
                    </template>
                </p>
                <p class="nova-muted" style="margin-top: 10px">{{ t('shell.noCharge') }}</p>
            </div>
            <div class="nova-card">
                <span class="nova-badge">{{ t('landing.demoBadge') }}</span>
                <span class="nova-muted"> {{ t('landing.demoMeta') }}</span>
                <pre class="nova-demo-out">{{ t('landing.demoPreview') }}</pre>
            </div>
        </div>

        <h2 style="margin-top: 36px">{{ t('shell.featuresTitle') }}</h2>
        <div class="nova-grid nova-grid-3">
            <div v-for="item in contentTypes" :key="item.title" class="nova-card">
                <h3>{{ t(item.title) }}</h3>
                <span class="nova-muted">{{ t(item.desc) }}</span>
            </div>
        </div>

        <h2 id="pricing" style="margin-top: 30px">{{ t('shell.pricingTitle') }}</h2>
        <p class="nova-muted">{{ t('shell.pricingSub') }}</p>
        <div class="nova-grid nova-grid-3">
            <div
                v-for="pkg in packages"
                :key="pkg.name"
                class="nova-card"
                :style="pkg.highlight ? 'border-color: var(--nova-pr)' : undefined"
            >
                <span v-if="pkg.highlight" class="nova-badge">{{ t('shell.bestValue') }}</span>
                <div class="nova-muted">{{ pkg.name }}</div>
                <div class="nova-credits-lg">
                    ${{ pkg.price }}
                    <small class="nova-muted">{{ pkg.credits }} credits</small>
                </div>
                <div class="nova-muted">{{ perCredit(pkg.price, pkg.credits) }}</div>
                <br />
                <button class="nova-btn w-100" :class="{ 'nova-btn-primary': pkg.highlight }" type="button">
                    {{ t('shell.buyStripe') }}
                </button>
            </div>
        </div>

        <h2 style="margin-top: 30px">{{ t('shell.faqTitle') }}</h2>
        <div class="nova-card">
            <b>{{ t('landing.faqCredit') }}</b><br />
            <span class="nova-muted">{{ t('landing.faqCreditAnswer') }}</span>
        </div>
        <div class="nova-card">
            <b>{{ t('landing.faqFail') }}</b><br />
            <span class="nova-muted">{{ t('landing.faqFailAnswer') }}</span>
        </div>
        <div class="nova-card">
            <b>{{ t('landing.faqBurmese') }}</b><br />
            <span class="nova-muted">{{ t('landing.faqBurmeseAnswer') }}</span>
        </div>

        <div
            class="nova-row nova-muted"
            style="padding: 20px 0; border-top: 1px solid var(--nova-bd); margin-top: 20px"
        >
            <span>{{ t('shell.footerRights') }}</span>
            <span>{{ t('shell.footerTerms') }} · {{ t('shell.footerPrivacy') }}</span>
        </div>
    </section>
</template>
