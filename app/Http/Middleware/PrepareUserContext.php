<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrepareUserContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Always load role and permissions first
        $user->load(['role.permissions', 'permissions']);

        if ($user->company_id) {
            // Company user - load company and area access
            $user->load(['company', 'areaAccess']);
            $user->allowedAreas = $user->areaAccess->pluck('area_id')->toArray();
            $user->hasAreaRestrictions = !empty($user->allowedAreas);
        } else {
            // System user (Super Admin, etc.) - no company or area restrictions
            $user->allowedAreas = [];
            $user->hasAreaRestrictions = false;
        }

        // Set effective permissions (role permissions + user-specific permissions)
        $user->effectivePermissions = $user->getAllPermissions()->pluck('name')->toArray();

        return $next($request);
    }
}
