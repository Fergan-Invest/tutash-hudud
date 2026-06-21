<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Mahalla;
use App\Models\RegistryRequest;
use App\Models\Street;
use App\Policies\AuditPolicy;
use App\Policies\MahallaPolicy;
use App\Policies\RegistryRequestPolicy;
use App\Policies\StreetPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        RegistryRequest::class => RegistryRequestPolicy::class,
        Street::class => StreetPolicy::class,
        Mahalla::class => MahallaPolicy::class,
        AuditLog::class => AuditPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
    }
}
