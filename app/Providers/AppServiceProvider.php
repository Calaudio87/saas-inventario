<?php

namespace App\Providers;

use App\Models\User;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn () => new TenantContext());
    }

    public function boot(): void
    {
        Gate::define('manage-catalog', fn (User $user): bool => $user->isAdmin());
        Gate::define('manage-users', fn (User $user): bool => $user->isAdmin());
        Gate::define('record-movements', fn (User $user): bool => $user->isAdmin() || $user->isStaff());
    }
}

