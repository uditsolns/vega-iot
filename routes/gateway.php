<?php

/**
 * Gateway routes for vendor IoT device data ingestion.
 *
 * These routes are intentionally unauthenticated — IoT devices cannot
 * carry API tokens. Rate limiting and IP allow-listing should be
 * enforced at the infrastructure/load-balancer level.
 */

use App\Http\Controllers\Gateway\AliterGatewayController;
use App\Http\Controllers\Gateway\IdeabyteGatewayController;
use App\Http\Controllers\Gateway\SunsuiGatewayController;
use App\Http\Controllers\Gateway\TZoneGatewayController;
use App\Http\Controllers\Gateway\ZionGatewayController;
use Illuminate\Support\Facades\Route;

Route::post('zion', [ZionGatewayController::class, 'handle']);
Route::post('tzone', [TZoneGatewayController::class, 'handle']);
Route::post('ideabyte', [IdeabyteGatewayController::class, 'handle']);
Route::post('aliter', [AliterGatewayController::class, 'handle']);
Route::post('sunsui', [SunsuiGatewayController::class, 'handle']);
