<?php

namespace App\Providers;

use App\Models\User;
use Dedoc\Scramble\Scramble;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

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
        // Strict Eloquent safety checks in development; relaxed in production
        Model::shouldBeStrict(! $this->app->isProduction());

        // Prohibit destructive database commands in production
        DB::prohibitDestructiveCommands($this->app->isProduction());

        // Tiered API Rate Limiters
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api.auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('api.uploads', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Register Scramble API documentation under developer/docs/api prefix
        Scramble::ignoreDefaultRoutes();
        Scramble::registerUiRoute('developer/docs/api');
        Scramble::registerJsonSpecificationRoute('developer/docs/api.json');

        // Gate for Scramble API documentation
        Gate::define('viewApiDocs', function (?User $user = null): bool {
            return (bool) session()->get('developer_authenticated', false)
                || app()->environment('local', 'testing');
        });

        // Gate for Log Viewer
        Gate::define('viewLogViewer', function (?User $user = null): bool {
            return (bool) session()->get('developer_authenticated', false)
                || app()->environment('local', 'testing');
        });

        // Gate for Telescope
        Gate::define('viewTelescope', function (?User $user = null): bool {
            return (bool) session()->get('developer_authenticated', false)
                || app()->environment('local', 'testing');
        });

        // Gate for Horizon
        Gate::define('viewHorizon', function (?User $user = null): bool {
            return (bool) session()->get('developer_authenticated', false)
                || app()->environment('local', 'testing');
        });
    }
}
