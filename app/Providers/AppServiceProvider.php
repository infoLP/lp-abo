<?php
namespace App\Providers;

use App\Models\Magazine;
use App\Models\MagazineIssue;
use App\Policies\MagazineIssuePolicy;
use App\Policies\MagazinePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // HTTPS forcé en production
        if (config('app.force_https', false) || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // ── Enregistrement des Policies ──────────────────────────────
        Gate::policy(Magazine::class,      MagazinePolicy::class);
        Gate::policy(MagazineIssue::class, MagazineIssuePolicy::class);

        // ── Gate admin preview ───────────────────────────────────────
        // Rôles corrigés : manager (pas gestionnaire), director (pas directeur)
        Gate::define('admin-preview', function ($user) {
            return $user->is_active
                && $user->hasAnyRole(['admin', 'director', 'manager', 'accountant']);
        });
    }
}
