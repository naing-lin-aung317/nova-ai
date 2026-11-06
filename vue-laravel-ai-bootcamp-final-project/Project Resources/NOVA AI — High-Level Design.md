# NOVA AI — High-Level Design

AI SaaS Content Generation Platform | Version 1.2 (updated from v1.1) | 4 November 2026

Vue 3 • Laravel • MySQL • Redis • Bootstrap 5 • Stripe (USD) • VPS + Nginx

## Document Information

| Item | Decision |
| --- | --- |
| Document | High-Level Design (HLD) |
| Version | 1.2 (replaces v1.1) |
| Audience | Product owner, designer, frontend/backend developers, QA |
| Status legend | **(new)** added in v1.2, **(changed)** modified in v1.2, **(proposed)** my recommendation, needs your confirmation |

### Changelog from v1.1

- **Removed:** custom credit purchase. Credits are bought only through fixed packages. The custom-amount rate, minimum and maximum settings are deleted.
- **Changed:** failed or cancelled generations are shown as "no charge" (release), never as "refunded".
- **Changed:** Section 17 now uses the 9-phase roadmap.
- **New:** abuse protection, content-safety handling, capacity limits, refunds/chargebacks, credit expiry, account deletion, time zones, email provider, API conventions, generation length field, operations targets, UI states and the complete screen list (Section 20).
- **Changed:** Redis is required, not optional.
- **Changed:** the demo "Switch to Admin" control is removed from the product.

## 1. Project Overview

NOVA AI is a multi-user web application that helps individual creators and small businesses write marketing and social media content with Google Gemini. Users choose a content type, language and tone, manage their content, and buy credits. It supports English and Burmese and works on desktop, tablet and mobile.

| Item | Decision |
| --- | --- |
| Product name | NOVA AI (working name; check domain and trademark before launch) |
| Application type | Multi-user SaaS web application |
| Frontend | Vue 3 + Vite + Vue Router + Pinia + Axios + Bootstrap 5 + custom CSS + Vue I18n |
| Backend | Laravel REST API, Sanctum cookie SPA authentication |
| Database / cache | MySQL; Redis (required for locks, queues, rate limits) **(changed)** |
| AI provider | Google Gemini Flash; model ID in server configuration |
| Payments | Stripe Checkout, USD only |
| Hosting | VPS with Nginx + PHP-FPM |
| Core credit rule | 20 free credits at signup; 1 credit per successful generation; nothing is charged when generation fails |

## 2. Goals and Main Features

- Generate five content types: TikTok script, Facebook post, marketing copy, social media caption, blog post.
- Generate in English or Burmese, with Short / Medium / Long length **(new)**.
- Built-in tones plus personal custom tones.
- Streaming output with Stop button.
- View, search, edit, copy, save, delete and regenerate own content.
- 20 free credits once per account.
- Buy fixed credit packages in USD through Stripe. No custom amounts, no subscriptions **(changed)**.
- Dashboard, billing page, history, settings and SuperAdmin area.
- Light, Dark and System themes; English and Burmese interface.

Not in the first release: image/video/voice generation, social autopublishing, team workspaces, customer API keys, native mobile apps, recurring subscriptions, custom-amount credit purchase.

## 3. Users and Permissions

| Role | Can do |
| --- | --- |
| Visitor | View landing page and packages; register, log in. |
| User | Generate, manage own tones and content, view own credits/payments, buy packages, edit profile, delete account. |
| SuperAdmin | Manage users, packages, built-in tones, payments, credit adjustments, AI settings; view audit log and monitoring. |

Status values: Active, Suspended, Pending verification. A suspended user cannot log in or generate. The role is controlled only by the backend; no role-switch control exists in the UI **(changed)**.

**Important:** Laravel checks permission on the server for every private request. Changing an ID in a URL or API call must never expose another user's data. Every admin action is written to the audit log.

A limited "Support" admin role is not in the MVP, but the role field must allow adding it later **(new)**.

## 4. Application Pages

See Section 20 for the full screen list. Main user pages: Landing, Register, Login, Verify email, Forgot/Reset password, Dashboard, Generator, Custom Tones, History, Billing, Settings. Admin pages: Overview, Users, Packages, Built-in Tones, Payments, Monitoring, Audit Log, AI Settings.

## 5. Content Generation Rules

- Topic or product details are required (maximum length enforced). Audience, keywords, call to action are optional.
- Length options **(new)**: Short, Medium, Long. Each content type has a maximum output size per length, tested separately for English and Burmese (Burmese uses more tokens).
- Users pick a built-in or own custom tone. Custom tone sample content has a character limit **(new)**.
- Output streams progressively and can be stopped by the user.
- Editing saved text costs no credit. Regeneration is a new request and costs 1 credit only on success.
- Treat tone text and topic input as untrusted: separate it from system instructions in the prompt to reduce prompt injection **(new)**.
- Content safety **(new)**: if Gemini blocks a request for safety reasons, the user sees a clear message, the generation is marked Failed, and no credit is charged.
- Keep the Gemini model ID in server configuration. Confirm model, streaming, quota and price in the Google AI project before implementation.
- Per-user concurrent generation limit (proposed: 1 at a time, 2 maximum) **(new)**.

## 6. Credit System

Only the backend changes balances.

| Rule | Behavior |
| --- | --- |
| Signup bonus | 20 credits once per account, granted after email verification **(confirmed)**. |
| Success | Charge 1 credit after the result is complete and saved. |
| Failed / cancelled | Charge zero. Release the reservation immediately. Applies to Gemini error, quota error, safety block, timeout, network drop, server error and user Stop. Shown in the UI as "No charge". |
| No credits | Block generation before calling Gemini and link to Billing. |
| Expiry **(new)** | Purchased and bonus credits never expire. |
| Free actions | Tones, history search/edit/copy/delete are free. |

Safe process: check available credits → reserve 1 → call Gemini → on success save output and capture → on failure or cancel release.

**Stuck reservations:** a scheduled job releases any reservation pending longer than the timeout (proposed: 5 minutes; Gemini request timeout 2 minutes). Pending reservations are monitored.

Every credit event (grant, purchase, reserve, capture, release, refund, admin adjustment, chargeback clawback) is a ledger row. Use database transactions, row locking and idempotency keys. Wallet balance must always equal the ledger sum; a nightly reconciliation job reports mismatches **(new)**.

## 7. Billing and Payments

### 7.1 Provider and currency

- Stripe Checkout (hosted page), USD only, one-time purchases only.
- Payment code sits behind a provider interface.
- Manual admin verification is an internal support fallback only.

### 7.2 Credit packages

Packages live in `credit_packages` and are editable by SuperAdmin without code changes. Packages can be Visible or Hidden; hidden packages stay on past payments but cannot be bought. Sold packages are hidden, not deleted.

| Package | Price (USD) | Credits | Per credit |
| --- | --- | --- | --- |
| Starter | $10 | 20 | $0.50 |
| Creator | $30 | 75 | $0.40 |
| Agency | $100 | 300 | $0.33 |

v1.1 had Growth at 50 credits ($0.60), which cost more per credit than Starter. v1.2 proposes 75 credits for $30 so larger packages are always cheaper per credit. Names and amounts are confirmed.

### 7.3 Payment flow

1. User selects a package on Billing.
2. Laravel creates a pending USD order and a Stripe Checkout Session.
3. User pays on Stripe and returns to NOVA AI.
4. Stripe sends a signed webhook; Laravel verifies the signature.
5. Only after the verified webhook does Laravel add credits and mark the order Paid.

### 7.4 Payment rules

- A browser success page is not proof of payment.
- Process each Stripe event once (event ID + order ID).
- Failed, cancelled or expired payments never add credits.
- Refunds and disputes **(new)**: a full refund removes the purchased credits through a ledger row. If the user has already spent them, the balance may go negative and the user cannot generate until it is cleared; the admin can adjust with a reason. Dispute handling is reviewed manually.
- Stripe keys only in server environment variables.
- Confirm refund policy, taxes and Stripe live activation before real money.

## 8. System Architecture

Modular monolith: one Laravel backend with separate modules for accounts, generation, credits, payments and administration. Request flow: Vue → Laravel API → MySQL; Laravel also calls Gemini and Stripe. Secret keys stay on the server. No microservices in the MVP.

## 9. Technology Stack

| Area | Technology |
| --- | --- |
| Frontend | Vue 3, Vite, Vue Router, Pinia, Axios, Bootstrap 5, Vue I18n |
| Streaming | Browser Fetch + ReadableStream |
| Backend | Laravel REST API, Sanctum, Pest tests |
| Data | MySQL, Redis |
| AI | Google Gemini API (configurable Flash model) |
| Payments | Stripe Checkout + webhooks |
| Email | Transactional provider (SMTP, SES or Mailgun) **(new)**, translated templates |
| Web server | Nginx + PHP-FPM on a VPS |
| Monitoring | Application logs, error monitoring, usage metrics |

## 10. Streaming Output

- Frontend sends the request with Fetch; Laravel streams Gemini chunks to the browser.
- Show a Generating state, a Stop button, and a final success or error event.
- Error messages say that no credit was charged.
- Store generation ID and status so the result can be recovered after a disconnect.
- Never blindly retry after partial output; reuse the idempotency key.
- Nginx must not buffer the streaming route (`proxy_buffering off` / `fastcgi_buffering off`, header `X-Accel-Buffering: no`).
- Each stream holds a PHP-FPM worker: size workers for the expected concurrency and enforce the per-user limit **(new)**.

## 11. Main Database Tables

| Table | Purpose |
| --- | --- |
| users | Account, role, status, email verification, language, theme, time zone. |
| credit\_wallets | Available and reserved balance per user. |
| credit\_transactions | Ledger of every credit event. |
| credit\_reservations | Temporary holds linked to generations. |
| generation\_requests | Input, output, type, language, tone, length, model, status, timing, failure reason. |
| custom\_tones / built\_in\_tones | User tones; SuperAdmin-managed defaults. |
| credit\_packages | Name, credits, USD price, visibility, sort order. |
| payment\_orders | USD amount, Stripe IDs, status, credits to issue. |
| payment\_webhook\_events | Stripe event IDs, to prevent duplicates. |
| api\_usage\_logs | Model usage, tokens, response times. |
| system\_settings | Non-secret settings (limits, timeouts, model ID). |
| audit\_logs | Admin and security actions. |

Generations use statuses: Pending, Streaming, Completed, Stopped, Failed. All user data links to its user with foreign keys. Never store passwords or API secrets as ordinary settings.

## 12. API Conventions and Groups

Conventions **(new)**: prefix `/api/v1`; JSON errors with `code`, translated `message` and field errors; cursor or page pagination with a maximum page size; ISO 8601 UTC timestamps; idempotency-key header on generation and payment creation; rate-limit headers.

| Group | Endpoints (examples) |
| --- | --- |
| Auth | register, login, logout, me, forgot/reset password, verify email, profile, password, delete account |
| Dashboard and wallet | dashboard, wallet, wallet/transactions |
| Tones | tones, custom-tones CRUD |
| Generation | create (stream), list/search, show, update (edit), delete, regenerate, cancel |
| Billing | credit-packages, payment-orders, payments |
| Webhook | POST /webhooks/stripe |
| Admin | users, suspend, credit adjustment, packages, built-in tones, payments, audit logs, monitoring, settings |

## 13. Security and Privacy

- Sanctum, CSRF protection, HTTPS, secure cookies, password hashing, login rate limits.
- Ownership and admin checks on every private endpoint.
- Validate all input; maximum prompt and output length.
- AI output is untrusted text: escape it, never render as HTML.
- Rate limits on registration, login, generation, tone creation, payments.
- Verify Stripe signatures; process each event once. No raw card data.
- Free-credit abuse protection **(new)**: email verification before bonus, disposable-email blocking, CAPTCHA on registration, IP/device rate limit.
- Keep personal data and prompts out of application logs **(new)**.
- Publish Privacy Policy and Terms (placeholders until final text is supplied), retention rules and account-deletion process.
- Account deletion **(new)**: requires password confirmation; removes profile, tones and generated content; keeps payment, ledger and audit records (anonymised) for accounting and legal needs.
- Gemini free-tier warning: quotas, data handling and availability can change. Disclose the external AI provider and avoid sending sensitive data.

## 14. Theme and Language

- Themes: Light, Dark, System (default System). Saved to the account; browser storage for visitors. Shared CSS variables.
- Languages: English and Burmese. Default from browser language (`my` → Burmese). Saved to account or browser. Switcher beside the theme switcher; changes apply instantly. Settings contains each switcher once.
- One Vue I18n file per language with identical keys; missing Burmese falls back to English; no raw keys on screen.
- Interface language and content language are separate.
- Server messages and emails are translated.
- `lang` attribute `en` or `my`; Unicode Burmese (not Zawgyi) with Noto Sans Myanmar and extra line height; check wrapping on mobile.
- Prices stay in USD. Confirm digit style. Stripe Checkout opens in a language Stripe supports.
- Native-speaker review of Burmese before launch.
- Dates stored in UTC and shown in the user's time zone (proposed default: Asia/Yangon) **(new)**.

## 15. Deployment and Operations

- VPS with Nginx + PHP-FPM, MySQL and Redis; only ports 80/443 exposed; firewall and SSH keys.
- Separate dev, staging and production with separate keys and databases. A staging VPS is used to verify streaming early **(changed)**.
- Same top-level domain for frontend and API.
- HTTPS with automatic renewal.
- Queue workers under Supervisor/systemd; Laravel scheduler by cron (email, webhook follow-up, reservation cleanup, reconciliation).
- Controlled migrations with backups first.
- Off-VPS automated backups, tested restores. Proposed targets **(new)**: daily backups, RPO 24 h, RTO 4 h, log retention 30 days.
- Monitor generation failures, latency, quota errors, pending reservations, wallet/ledger mismatches, webhook failures, VPS resources, unusual admin adjustments.

## 16. Testing and Acceptance Checklist

- New account receives exactly 20 credits once, and only after verification.
- Zero credits cannot start generation.
- Success charges exactly one credit; failure, safety block, timeout, drop or Stop charges zero and leaves no stuck reservation.
- Two simultaneous requests cannot spend the same credit; concurrent-generation limit works.
- Streaming is progressive, recoverable after disconnect, and not buffered by Nginx.
- Users cannot see or change another user's content or tones.
- Edit is free; regeneration charges only on success.
- Each package adds the right credits after a verified Stripe payment; prices are USD.
- Unverified, failed, cancelled or expired payments add no credits; repeated webhooks add credits once; invalid signatures are rejected.
- Refund and chargeback ledger rows work.
- Suspended users cannot log in or generate.
- Admin credit changes require a reason and are audited; wallet/ledger reconciliation flags mismatches.
- Light/Dark/System and English/Burmese work on every page and persist; Burmese renders correctly; no raw keys; interface language does not change content language.
- All five content types work in English and Burmese.
- Desktop, tablet and mobile layouts usable; loading, empty and error states exist on every screen.
- Account deletion keeps required financial records.

## 17. Development Plan (9 Phases) (changed)

One phase at a time. Before each phase: implementation plan, affected files, database changes, API endpoints, acceptance criteria, then wait for approval. After each phase: run tests, verify in browser, completion report, then wait for review. Never start the next phase automatically. Each phase writes its own Pest tests.

| Phase | Work | Result |
| --- | --- | --- |
| 1. Foundation & Frontend Setup | Replace starter stack (confirm what first); Vue 3, Router, Pinia, Axios, Bootstrap 5, Vue I18n; MySQL; theme and language infrastructure; Burmese font; single-page shell with public, app and admin layouts. | Theme and language switch instantly; empty shell runs. |
| 2. Authentication & User Management | Sanctum; register, login, logout; forgot/reset; email verification; roles and status; landing and auth screens; settings, password, account deletion; sessions; security tests. | Users can register, verify and log in safely. |
| 3. Credit Wallet & Ledger | Wallet and ledger tables; 20-credit bonus after verification; wallet and transaction APIs; audit\_logs table; consistency tests. | Balances always match the ledger. |
| 4. Custom Tones & Dashboard | Built-in tones; custom tone CRUD; ownership policies; dashboard with real balance and credit activity. | Tones and dashboard work. |
| 5. AI Generation Engine | Gemini; reserve/capture/release; locking; idempotency; streaming; failure, timeout, cancel and safety handling; cleanup job; concurrency tests. | Failures never cost credits. |
| 6. Generator UI & Content History | Generator, five types, EN/MY, length, streaming and Stop; history list/detail/search/filter; edit, delete, regenerate; dashboard recent content; early staging Nginx streaming check. | Users generate and manage content. |
| 7. Billing & Stripe Integration | Packages; Stripe Checkout; signed webhooks; duplicate protection; refund and chargeback handling; payment history; billing screen; Stripe test purchases. | Verified USD purchases add credits once. |
| 8. Admin Dashboard & Monitoring | Overview; users and suspend; credit adjustments; packages; built-in tones; payments; AI settings; audit log; failure and webhook monitoring; reconciliation. | Admin can operate the platform. |
| 9. Final QA & Production Release | Full acceptance; Pest regression; EN/MY and theme testing; security review; production config; VPS + Nginx, MySQL, Redis, queue workers; HTTPS and streaming check; Privacy/Terms placeholders. | MVP released. |

## 18. Decisions

Decided: Stripe, USD, fixed packages only (no custom purchase), no charge on failure, VPS + Nginx, Redis required, 9-phase plan, package names and credits (Starter $10 / 20, Creator $30 / 75, Agency $100 / 300), and signup bonus granted only after email verification.

Still open (my recommendation in brackets):

- Gemini model, quota and cost.
- Stripe live-payment eligibility; refund policy; taxes.
- Data retention period for prompts and content.
- Expected users, volume and VPS size.
- NOVA AI name, domain and trademark.
- Email provider.
- Burmese or Western digits for prices and credit counts.
- Display time zone default (Asia/Yangon).
- What "existing starter stack" in Phase 1 refers to.

These do not block UI and authentication work, but live payments and production AI usage must wait for them.

## 19. Final Recommendation

Build in small phases: authentication and permissions, then the credit ledger, tones, and AI generation (a failed generation never costs a credit), then Stripe in test mode, then admin and release. Keep the Gemini model and payment provider configurable, and test failures and duplicates before accepting real money.

## 20. Screen List (matches the prototype)

| Area | Screens |
| --- | --- |
| Public | Landing (with FAQ), Login and Register (split layout with an animated live example, Back to home link, show/hide password, Terms checkbox, password strength), Verify email, Forgot password, Reset password |
| User | Dashboard (low-credit and empty states), Generator (Idle, Generating, Done, Failed, No credits), Tones (create, edit, delete confirm), History (search, filters, detail, edit, copy, regenerate, delete), Billing (3 packages, payments, ledger), Settings (profile, theme, language, password, delete account) |
| Admin | Overview, Users (suspend, adjust credits), Packages (visible/hidden), Built-in Tones, Payments, Monitoring (reconciliation, reservations, webhooks), Audit Log, AI Settings |
| Global | Responsive sidebar, Light/Dark/System, EN/Burmese, mobile menu |

## Glossary

| Term | Meaning |
| --- | --- |
| Reservation | Temporary hold on a credit during generation |
| Capture | Final charge of a reserved credit after success |
| Release | Return of a reservation with no charge |
| Idempotency | A retry does not repeat the same action |
| Webhook | Server-to-server payment message |
| Reconciliation | Check that wallet balances equal the ledger |
