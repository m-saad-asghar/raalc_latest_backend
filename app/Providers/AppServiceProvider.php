<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use App\Services\WebContentCacheService;

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
        set_time_limit(120);

        // Auto flush the WebContent file cache whenever ANY Eloquent model
        // is created, updated or deleted (no module-specific filtering).
        $flush = function ($model) {
            try {
                WebContentCacheService::flush();
            } catch (\Throwable $e) {
                // swallow - cache invalidation must not break writes
            }
        };

        Model::saved($flush);   // fires on create + update
        Model::deleted($flush); // fires on delete (and soft delete)
    }
}
