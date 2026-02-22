<?php

use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CalibrationInstrumentController;
use App\Http\Controllers\Device\DeviceConfigurationController;
use App\Http\Controllers\Device\DeviceController;
use App\Http\Controllers\Device\DeviceModelController;
use App\Http\Controllers\Device\DeviceSensorController;
use App\Http\Controllers\Hierarchy\AreaController;
use App\Http\Controllers\Hierarchy\CompanyController;
use App\Http\Controllers\Hierarchy\HierarchyController;
use App\Http\Controllers\Hierarchy\HubController;
use App\Http\Controllers\Hierarchy\LocationController;
use App\Http\Controllers\Report\AuditReportController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Report\ScheduledReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\User\UserAreaController;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\User\UserPermissionController;
use App\Http\Controllers\ValidationStudyController;
use App\Models\SensorType;
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

        // Companies
        Route::apiResource("companies", CompanyController::class);
        Route::controller(CompanyController::class)->group(function () {
            Route::patch("companies/{company}/activate", "activate");
            Route::patch("companies/{company}/deactivate", "deactivate");
            Route::patch("companies/{id}/restore", "restore");
        });

        // Users - Basic operations
        Route::apiResource("users", UserController::class);
        Route::controller(UserController::class)
            ->prefix("users")
            ->group(function () {
                Route::patch("{user}/activate", "activate");
                Route::patch("{user}/deactivate", "deactivate");
                Route::patch("{user}/roles", "changeRole");
                Route::post("{user}/reset-password", "resetPassword");
                Route::patch("{id}/restore", "restore");
            });

        // Users - Area management
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
                Route::patch("{id}/restore", "restore");
            });


        // Device Models (Super Admin only)
        Route::prefix('device-models')
            ->controller(DeviceModelController::class)
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::get('{deviceModel}', 'show');
                Route::delete('{deviceModel}', 'destroy');
            });

        // Sensor Types (read-only for all authenticated users)
        Route::get('sensor-types', fn() => response()->json([
            'data' => SensorType::all()->map(fn($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'unit' => $t->unit,
                'data_type' => $t->data_type->value,
                'supports_threshold_config' => $t->supports_threshold_config,
            ])
        ]));

        // Devices
        Route::get('devices/stats', [DeviceController::class, 'getStats']);

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
            });

        // Device Configuration
        Route::prefix('devices/{device}/configuration')
            ->controller(DeviceConfigurationController::class)
            ->scopeBindings()
            ->group(function () {
                Route::get('/', 'show');
                Route::put('/', 'update');
                Route::get('history', 'history');
            });

        // Device Sensors
        Route::prefix('devices/{device}/sensors')
            ->controller(DeviceSensorController::class)
            ->scopeBindings()
            ->group(function () {
                Route::get('/', 'index');
                Route::patch('{sensor}', 'update');
                Route::get('{sensor}/configuration', 'showConfiguration');
                Route::put('{sensor}/configuration', 'updateConfiguration');
                Route::get('{sensor}/configuration/history', 'configurationHistory');
            });

        // Alerts
        Route::prefix("alerts")
            ->controller(AlertController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::get("{alert}", "show");
                Route::patch("{alert}/acknowledge", "acknowledge");
                Route::patch("{alert}/resolve", "resolve");
            });

        // Tickets - Enhanced with lifecycle actions
        Route::apiResource("tickets", TicketController::class);
        Route::prefix("tickets")->group(function () {
            Route::controller(TicketController::class)->group(function () {
                Route::patch("{ticket}/assign", "assign");
                Route::patch("{ticket}/resolve", "resolve");
                Route::patch("{ticket}/close", "close");
                Route::patch("{ticket}/reopen", "reopen");
            });

            Route::controller(TicketCommentController::class)->group(
                function () {
                    Route::get("{ticket}/comments", "index");
                    Route::post("{ticket}/comments", "store");
                },
            );
        });

        // Audit Logs
        Route::prefix("audit-logs")
            ->controller(AuditLogController::class)
            ->group(function () {
                Route::get("/", "index");
            });

        // Reports
        Route::prefix("reports")
            ->controller(ReportController::class)
            ->group(function () {
                Route::get("/", "index");
                Route::post("/", "store");
                Route::get('{report}/download', 'download');
        });

        // Scheduled Reports
        Route::apiResource("scheduled-reports", ScheduledReportController::class);
        Route::prefix('scheduled-reports')
            ->controller(ScheduledReportController::class)
            ->group(function () {
                Route::patch('{scheduledReport}/toggle', 'toggle');
                Route::get('{scheduledReport}/executions', 'executions');
                Route::post('{scheduledReport}/test', 'testRun');
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
