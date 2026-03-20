<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
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
    }
}
