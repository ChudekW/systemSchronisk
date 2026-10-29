<?php

namespace App\Providers;

use App\Models\Animal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        Gate::define('manage-animals', fn (User $user): bool => ! $user->is_super_admin && $user->organization?->is_active === true);
        Gate::define('access-animal', fn (User $user, Animal $animal): Response => $user->can('manage-animals') && $user->organization_id === $animal->organization_id
            ? Response::allow()
            : Response::denyAsNotFound());
        Gate::define('manage-organizations', fn (User $user): bool => $user->is_super_admin === true);
        Gate::define('manage-own-organization', fn (User $user): bool => ! $user->is_super_admin && $user->is_organization_admin && $user->organization?->is_active === true
        );
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
