<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
        /* $this->registerPolicies(); */

        // Регистрация Gates для каждой роли
        Gate::define('access-super-admin', function ($user) {
            return $user->role === 'super_admin';
        });

        Gate::define('access-admin', function ($user) {
            return $user->role === 'hr';
        });

        Gate::define('access-moderator', function ($user) {
            return $user->role === 'employer';
        });
    }
}
