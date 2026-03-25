<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Resolve factories for domain models that live under App\Domain\*\Models\*
        // by mapping them to the top-level Database\Factories namespace.
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            // Extract the short class name (e.g. "User" from "App\Domain\Staff\Models\User")
            $shortName = class_basename($modelName);

            return 'Database\\Factories\\' . $shortName . 'Factory';
        });

        RateLimiter::for('auth-login', function (Request $request): Limit {
            $email = Str::lower((string) $request->input('email', ''));

            return Limit::perMinute(1)->by($request->ip() . '|' . $email);
        });
    }
}
