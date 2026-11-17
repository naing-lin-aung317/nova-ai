<?php

namespace App\Providers;

use App\Contracts\AiProvider;
use App\Contracts\PaymentGateway;
use App\Listeners\CreateWalletForNewUser;
use App\Payments\FakePaymentGateway;
use App\Payments\StripePaymentGateway;
use App\Services\Ai\GeminiProvider;
use App\Services\NovaSettingsService;
use App\Services\PaymentService;
use App\Services\WalletService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AiProvider::class, GeminiProvider::class);

        $this->app->bind(PaymentGateway::class, function () {
            $stripe = config('nova.stripe');

            if ($stripe['fake'] || app()->environment('testing')) {
                return new FakePaymentGateway;
            }

            $secret = $stripe['secret'];

            if (! is_string($secret) || $secret === '') {
                throw new \RuntimeException('STRIPE_SECRET is not configured.');
            }

            return new StripePaymentGateway($secret);
        });

        $this->app->singleton(PaymentService::class, function ($app) {
            $stripe = config('nova.stripe');

            return new PaymentService(
                gateway: $app->make(PaymentGateway::class),
                wallets: $app->make(WalletService::class),
                webhookSecret: is_string($stripe['webhook_secret'] ?? null) ? $stripe['webhook_secret'] : null,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(Registered::class, CreateWalletForNewUser::class);

        app(NovaSettingsService::class)->mergeIntoConfig();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
