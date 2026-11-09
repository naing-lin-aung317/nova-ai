<?php

namespace App\Providers;

use App\Contracts\GeminiClient;
use App\Gemini\FakeGeminiClient;
use App\Gemini\HttpGeminiClient;
use App\Listeners\CreateWalletForNewUser;
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
        $this->app->bind(GeminiClient::class, function () {
            if (config('nova.gemini.fake') || app()->environment('testing')) {
                return new FakeGeminiClient;
            }

            return new HttpGeminiClient(
                apiKey: config('nova.gemini.api_key'),
                model: (string) config('nova.gemini.model'),
                timeoutSeconds: (int) config('nova.gemini.request_timeout_seconds'),
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
