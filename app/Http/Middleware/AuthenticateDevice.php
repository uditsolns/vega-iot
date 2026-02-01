<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
//        // Extract device ID from request body (field: 'ID')
//        $deviceId = $request->input('ID');
//
//        if (!$deviceId) {
//            return response()->json([
//                'message' => 'Device ID (ID field) is required',
//            ], 401);
//        }

        // Query devices table for matching device_uid
        $device = Device::where('api_key', $request->bearerToken())
            ->with(['area.hub.location', 'company'])
            ->first();

        // Check device exists
        if (!$device) {
            return response()->json([
                'message' => 'Device not found',
            ], 401);
        }

        // Check device is active
        if (!$device->is_active) {
            return response()->json([
                'message' => 'Device is not active',
            ], 401);
        }

        // Attach device to request
        $request->merge(['device' => $device]);

        return $next($request);
    }
}
