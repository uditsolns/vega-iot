<?php

namespace App\Exceptions;

use Exception;

class DeviceAssignmentException extends Exception
{
    /**
     * Create a new exception instance for device without company.
     */
    public static function deviceRequiresCompany(): self
    {
        return new self('Device must be assigned to a company before assigning to an area.');
    }

    /**
     * Create a new exception instance for area-company mismatch.
     */
    public static function areaMismatch(string $deviceCode, string $areaName): self
    {
        return new self("Device $deviceCode cannot be assigned to area $areaName - area belongs to a different company.");
    }

    /**
     * Create a new exception instance for unauthorized area access.
     */
    public static function unauthorizedAreaAccess(string $areaName): self
    {
        return new self("You do not have access to assign devices to area: $areaName");
    }
}
