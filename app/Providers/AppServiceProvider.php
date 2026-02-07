<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Area;
use App\Models\AuditReport;
use App\Models\CalibrationInstrument;
use App\Models\Company;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\Hub;
use App\Models\Location;
use App\Models\Report;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\ValidationStudy;
use App\Policies\AlertPolicy;
use App\Policies\AreaPolicy;
use App\Policies\AuditReportPolicy;
use App\Policies\CalibrationInstrumentPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\DevicePolicy;
use App\Policies\HubPolicy;
use App\Policies\LocationPolicy;
use App\Policies\ReadingPolicy;
use App\Policies\ReportPolicy;
use App\Policies\RolePolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;
use App\Policies\ValidationStudyPolicy;
use App\Services\Report\PDF\PdfGeneratorService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PdfGeneratorService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register policies
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(Hub::class, HubPolicy::class);
        Gate::policy(Area::class, AreaPolicy::class);
        Gate::policy(Device::class, DevicePolicy::class);
        Gate::policy(DeviceReading::class, ReadingPolicy::class);
        Gate::policy(Alert::class, AlertPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(CalibrationInstrument::class, CalibrationInstrumentPolicy::class);
        Gate::policy(ValidationStudy::class, ValidationStudyPolicy::class);
        Gate::policy(Report::class, ReportPolicy::class);
        Gate::policy(AuditReport::class, AuditReportPolicy::class);

        // Event listeners are auto-discovered from app/Listeners
        // No need to manually register them here
    }
}
