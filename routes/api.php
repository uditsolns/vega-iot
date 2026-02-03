<?php

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\AuditReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CalibrationInstrumentController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Device\DeviceBulkController;
use App\Http\Controllers\Device\DeviceConfigurationController;
use App\Http\Controllers\Device\DeviceController;
use App\Http\Controllers\HierarchyController;
use App\Http\Controllers\IngestController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\HubController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\User\UserAreaController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserPermissionController;
use App\Http\Controllers\ValidationStudyController;
use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    // Public routes (no authentication required)
    Route::prefix("auth")->group(function () {
        Route::post("login", [LoginController::class, "login"]);
        Route::controller(PasswordController::class)->group(function () {
            Route::post("forgot-password", "forgotPassword");
            Route::post("reset-password", "resetPassword")->name(
                "password.reset",
            );
        });
    });

    // Device ingestion routes (device authentication via API key)
    Route::middleware("auth.device")
        ->prefix("ingest")
        ->controller(IngestController::class)
        ->group(function () {
            Route::post("/", "store");
            Route::post("batch", "batch");
        });

    // Authenticated routes
    Route::middleware(["auth:sanctum", "prepare.user"])->group(function () {
        // Auth - Logout
        Route::post("auth/logout", [LoginController::class, "logout"]);

        // Profile
        Route::prefix("profile")
            ->controller(ProfileController::class)
            ->group(function () {
                Route::get("/", "show");
                Route::get("permissions", "permissions");
                Route::get("areas", "areas");

                // These bypass prepare.user middleware for performance
                Route::withoutMiddleware("prepare.user")->group(function () {
                    Route::put("/", "update");
                    Route::patch("password", "changePassword");
                });
            });

        // Dashboard
        Route::prefix("dashboard")
            ->controller(DashboardController::class)
            ->group(function () {
                Route::get("overview", "overview");
                Route::get("device-status", "deviceStatus");
                Route::get("active-alerts", "activeAlerts");
                Route::get("recent-activity", "recentActivity");
                Route::get("temperature-trends", "temperatureTrends");
                Route::get("alert-trends", "alertTrends");
                Route::get("top-devices-by-alerts", "topDevicesByAlerts");
            });

        // Companies
        Route::apiResource("companies", CompanyController::class);
        Route::controller(CompanyController::class)->group(function () {
            Route::patch("companies/{company}/activate", "activate");
            Route::patch("companies/{company}/deactivate", "deactivate");
            Route::patch("companies/{id}/restore", "restore");
        });

        // Users - Basic operations
        Route::get("users/export", [UserController::class, "export"]);
        Route::apiResource("users", UserController::class);
        Route::controller(UserController::class)
            ->prefix("users")
            ->group(function () {
                Route::patch("{user}/activate", "activate");
                Route::patch("{user}/deactivate", "deactivate");
                Route::patch("{user}/roles", "changeRole");
                Route::post("{user}/reset-password", "resetPassword");
                Route::post("{user}/resend-invite", "resendInvite");
                Route::get("{user}/activity", "activity");
                Route::patch("{id}/restore", "restore");
            });

        // Users - Area management (STUB for Phase 2)
        Route::prefix("users/{user}/areas")
            ->controller(UserAreaController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::post("/", "grantAreas");
                Route::post("by-location", "grantAreasByLocation");
                Route::post("by-hub", "grantAreasByHub");
                Route::delete("clear", "clearAreas");
                Route::delete("{area}", "revokeArea");
            });

        // Users - Permission management
        Route::prefix("users/{user}/permissions")
            ->controller(UserPermissionController::class)
            ->scopeBindings()
            ->group(function () {
                Route::get("/", "index");
                Route::post("/", "syncPermissions");
                Route::post("grant", "grantPermission");
                Route::delete("{permission}", "revokePermission");
            });

        // Roles
        Route::apiResource("roles", RoleController::class);
        Route::controller(RoleController::class)
            ->prefix("roles")
            ->group(function () {
                Route::post("{role}/clone", "clone");
                Route::get("{role}/users", "users");
            });

        // Hierarchy - Tree and navigation
        Route::prefix("hierarchy")
            ->controller(HierarchyController::class)
            ->group(function () {
                Route::get("tree", "tree");
                Route::get("locations", "locations");
                Route::get("locations/{location}/hubs", "locationHubs");
                Route::get("hubs/{hub}/areas", "hubAreas");
                Route::get("areas/{area}/breadcrumb", "breadcrumbArea");
                Route::get("devices/{device}/breadcrumb", "breadcrumbDevice");
                Route::get("search", "search");
            });

        // Locations
        Route::apiResource("locations", LocationController::class);
        Route::controller(LocationController::class)
            ->prefix("locations")
            ->group(function () {
                Route::patch("{location}/activate", "activate");
                Route::patch("{location}/deactivate", "deactivate");
                Route::get("{location}/hubs", "hubs");
                Route::get("{location}/devices", "devices");
                Route::get("{location}/stats", "stats");
                Route::get("{location}/alerts", "alerts");
                Route::patch("{id}/restore", "restore");
            });

        // Hubs
        Route::apiResource("hubs", HubController::class);
        Route::controller(HubController::class)
            ->prefix("hubs")
            ->group(function () {
                Route::patch("{hub}/activate", "activate");
                Route::patch("{hub}/deactivate", "deactivate");
                Route::get("{hub}/areas", "areas");
                Route::get("{hub}/devices", "devices");
                Route::get("{hub}/stats", "stats");
                Route::patch("{id}/restore", "restore");
            });

        // Areas
        Route::apiResource("areas", AreaController::class);
        Route::controller(AreaController::class)
            ->prefix("areas")
            ->group(function () {
                Route::patch("{area}/activate", "activate");
                Route::patch("{area}/deactivate", "deactivate");
                Route::patch("{area}/alert-config", "updateAlertConfig");
                Route::post("{area}/copy-alert-config", "copyAlertConfig");
                Route::get("{area}/devices", "devices");
                Route::get("{area}/stats", "stats");
                Route::get("{area}/alerts", "alerts");
                Route::get("{area}/readings", "getReadings");
                Route::patch("{id}/restore", "restore");
            });

        // Devices - Helper endpoints
        Route::get(
            "devices/types",
            fn() => response()->json([
                "data" => [
                    "types" => DeviceType::labels(),
                    "statuses" => DeviceStatus::labels(),
                ],
            ]),
        );
        Route::get("devices/stats", [DeviceController::class, "getStats"]);

        // Devices - Bulk operations
        Route::prefix("devices/bulk")
            ->controller(DeviceBulkController::class)
            ->group(function () {
                Route::post("assign-company", "bulkAssignToCompany");
                Route::post("assign-area", "bulkAssignToArea");
                Route::post("unassign", "bulkUnassign");
                Route::post("configure", "bulkConfigure");
                Route::post("status", "bulkChangeStatus");
                Route::post("delete", "bulkDelete");
            });

        // Devices - CRUD
        Route::apiResource("devices", DeviceController::class);

        // Devices - Actions
        Route::controller(DeviceController::class)
            ->prefix("devices")
            ->group(function () {
                Route::patch("{device}/activate", "activate");
                Route::patch("{device}/deactivate", "deactivate");
                Route::patch("{device}/status", "changeStatus");
                Route::post("{device}/assign-company", "assignToCompany");
                Route::post("{device}/assign-area", "assignToArea");
                Route::post("{device}/unassign", "unassign");
                Route::post("{device}/regenerate-api-key", "regenerateApiKey");
                Route::get("{device}/readings", "getReadings");
                Route::get("{device}/readings/latest", "getLatestReading");
                Route::get("{device}/alerts", "getAlerts");
                Route::patch("{id}/restore", "restore");

                Route::get("{device}/readings/available-dates", "getReadingsAvailableDates");
            });

        // Devices - Configuration (nested resource)
        Route::prefix("devices/{device}/configuration")
            ->controller(DeviceConfigurationController::class)
            ->scopeBindings()
            ->group(function () {
                Route::get("/", "show");
                Route::put("/", "update");
                Route::get("history", "history");
            });

        // Readings
        Route::prefix("readings")
            ->controller(ReadingController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::get("aggregations", "aggregations");
            });

        // Alerts
        Route::prefix("alerts")
            ->controller(AlertController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::get("statistics", "statistics");
                Route::get("{alert}", "show");
                Route::patch("{alert}/acknowledge", "acknowledge");
                Route::patch("{alert}/resolve", "resolve");
                Route::get("{alert}/notifications", "notifications");
            });

        // Tickets
        Route::prefix("tickets")->group(function () {
            Route::controller(TicketController::class)->group(function () {
                Route::get("/", "index");
                Route::post("/", "store");
                Route::get("{ticket}", "show");
                Route::put("{ticket}", "update");
                Route::delete("{ticket}", "destroy");
                Route::patch("{ticket}/status", "changeStatus");
                Route::patch("{ticket}/assign", "assign");
            });

            Route::controller(TicketCommentController::class)->group(
                function () {
                    Route::get("{ticket}/comments", "index");
                    Route::post("{ticket}/comments", "store");
                    Route::get(
                        "{ticket}/attachments/{attachment}",
                        "downloadAttachment",
                    )->name("api.v1.tickets.attachments.download");
                },
            );
        });

        // Audit Logs
        Route::prefix("audit-logs")
            ->controller(AuditLogController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::get("users/{user}/activity", "userActivity");
                Route::get("resource-history", "resourceHistory");
            });

        // Reports
        Route::prefix("reports")
            ->controller(ReportController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::post("/", "store");
                Route::get('{report}/download', 'download');
        });

        // Audit Reports
        Route::prefix('audit-reports')
            ->controller(AuditReportController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{auditReport}/download', 'download');
            });

        // Instruments
        Route::apiResource("calibration-instruments", CalibrationInstrumentController::class);

        // Studies
        Route::apiResource("validation-studies", ValidationStudyController::class);
    });
});
