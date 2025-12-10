<?php

namespace App\Services\Reading;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Events\ReadingReceived;
use App\Models\Device;
use App\Models\DeviceReading;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingIngestionService
{
    /**
     * Store a single reading for a device
     */
    public function store(Device $device, array $data): DeviceReading
    {
        // Validate the reading
        $this->validateReading($device, $data);

        // Denormalize hierarchy IDs
        $hierarchy = $this->denormalizeHierarchy($device);

        // Prepare reading data for insertion
        $readingData = [
            "device_id" => $device->id,
            "recorded_at" => $data["recorded_at"],
            "received_at" => now(),

            // Denormalized hierarchy
            "company_id" => $hierarchy["company_id"],
            "location_id" => $hierarchy["location_id"],
            "hub_id" => $hierarchy["hub_id"],
            "area_id" => $hierarchy["area_id"],

            // Sensor values
            "temperature" => $data["temperature"] ?? null,
            "humidity" => $data["humidity"] ?? null,
            "temp_probe" => $data["temp_probe"] ?? null,

            // Device metadata
            "battery_voltage" => $data["battery_voltage"] ?? null,
            "battery_percentage" => $data["battery_percentage"] ?? null,
            "wifi_signal_strength" => $data["wifi_signal_strength"] ?? null,

            // Operational metadata
            "firmware_version" =>
                $data["firmware_version"] ?? $device->firmware_version,

            // Raw payload for debugging
            "raw_payload" => $data,
        ];

        // Create using DeviceReading model for type safety
        $reading = DeviceReading::create($readingData);

        // Update device last_reading_at and status
        $device->update([
            "last_reading_at" => now(),
            "status" => DeviceStatus::Online,
        ]);

        // Dispatch ReadingReceived event
        event(new ReadingReceived($device, $readingData));

        return $reading;
    }

    /**
     * Store multiple readings in a batch
     */
    public function storeBatch(Device $device, array $readings): array
    {
        $results = [
            "success" => 0,
            "failed" => 0,
            "errors" => [],
        ];

        // Denormalize hierarchy once
        $hierarchy = $this->denormalizeHierarchy($device);

        $insertData = [];

        foreach ($readings as $index => $data) {
            try {
                // Validate each reading
                $this->validateReading($device, $data);

                $reading = [
                    "device_id" => $device->id,
                    "recorded_at" => $data["recorded_at"],
                    "received_at" => now(),
                    "company_id" => $hierarchy["company_id"],
                    "location_id" => $hierarchy["location_id"],
                    "hub_id" => $hierarchy["hub_id"],
                    "area_id" => $hierarchy["area_id"],
                    "temperature" => $data["temperature"] ?? null,
                    "humidity" => $data["humidity"] ?? null,
                    "temp_probe" => $data["temp_probe"] ?? null,
                    "battery_voltage" => $data["battery_voltage"] ?? null,
                    "battery_percentage" => $data["battery_percentage"] ?? null,
                    "wifi_signal_strength" =>
                        $data["wifi_signal_strength"] ?? null,
                    "firmware_version" =>
                        $data["firmware_version"] ?? $device->firmware_version,
                    "raw_payload" => json_encode($data),
                ];

                $insertData[] = $reading;
                $results["success"]++;
            } catch (ValidationException $e) {
                $results["failed"]++;
                $results["errors"][] = [
                    "index" => $index,
                    "errors" => $e->errors(),
                ];
            }
        }

        // Batch insert if we have valid readings
        if (!empty($insertData)) {
            DB::table("device_readings")->insert($insertData);

            // Update device once
            $device->update([
                "last_reading_at" => now(),
                "status" => DeviceStatus::Online,
            ]);

            // Dispatch events for each reading
            foreach ($insertData as $reading) {
                event(new ReadingReceived($device, $reading));
            }
        }

        return $results;
    }

    /**
     * Validate reading data against device type and constraints
     */
    public function validateReading(Device $device, array $data): void
    {
        $errors = [];

        // Required field: recorded_at
        if (!isset($data["recorded_at"])) {
            $errors["recorded_at"] = ["The recorded_at field is required."];
        } else {
            // Validate timestamp is not in the future
            $recordedAt = Carbon::parse($data["recorded_at"]);
            if ($recordedAt->isFuture()) {
                $errors["recorded_at"] = [
                    "The recorded_at cannot be in the future.",
                ];
            }
        }

        // Validate required sensors based on device type
        switch ($device->type) {
            case DeviceType::SingleTemp:
                if (!isset($data["temperature"])) {
                    $errors["temperature"] = [
                        "Temperature is required for single_temp devices.",
                    ];
                }
                break;

            case DeviceType::SingleTempHumidity:
                if (!isset($data["temperature"])) {
                    $errors["temperature"] = [
                        "Temperature is required for single_temp_humidity devices.",
                    ];
                }
                if (!isset($data["humidity"])) {
                    $errors["humidity"] = [
                        "Humidity is required for single_temp_humidity devices.",
                    ];
                }
                break;

            case DeviceType::DualTemp:
                if (!isset($data["temperature"])) {
                    $errors["temperature"] = [
                        "Temperature is required for dual_temp devices.",
                    ];
                }
                if (!isset($data["temp_probe"])) {
                    $errors["temp_probe"] = [
                        "Temperature probe is required for dual_temp devices.",
                    ];
                }
                break;

            case DeviceType::DualTempHumidity:
                if (!isset($data["temperature"])) {
                    $errors["temperature"] = [
                        "Temperature is required for dual_temp_humidity devices.",
                    ];
                }
                if (!isset($data["humidity"])) {
                    $errors["humidity"] = [
                        "Humidity is required for dual_temp_humidity devices.",
                    ];
                }
                if (!isset($data["temp_probe"])) {
                    $errors["temp_probe"] = [
                        "Temperature probe is required for dual_temp_humidity devices.",
                    ];
                }
                break;
        }

        // Validate sensor value ranges
        if (
            isset($data["temperature"]) &&
            ($data["temperature"] < -100 || $data["temperature"] > 200)
        ) {
            $errors["temperature"] = [
                "Temperature must be between -100 and 200.",
            ];
        }

        if (
            isset($data["humidity"]) &&
            ($data["humidity"] < 0 || $data["humidity"] > 100)
        ) {
            $errors["humidity"] = ["Humidity must be between 0 and 100."];
        }

        if (
            isset($data["temp_probe"]) &&
            ($data["temp_probe"] < -100 || $data["temp_probe"] > 200)
        ) {
            $errors["temp_probe"] = [
                "Temperature probe must be between -100 and 200.",
            ];
        }

        if (
            isset($data["battery_percentage"]) &&
            ($data["battery_percentage"] < 0 ||
                $data["battery_percentage"] > 100)
        ) {
            $errors["battery_percentage"] = [
                "Battery percentage must be between 0 and 100.",
            ];
        }

        if (
            isset($data["wifi_signal_strength"]) &&
            $data["wifi_signal_strength"] > 0
        ) {
            $errors["wifi_signal_strength"] = [
                "WiFi signal strength must be negative (dBm).",
            ];
        }

        // Throw validation exception if errors exist
        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Denormalize hierarchy IDs from device relationships
     */
    private function denormalizeHierarchy(Device $device): array
    {
        $hierarchy = [
            "company_id" => $device->company_id,
            "location_id" => null,
            "hub_id" => null,
            "area_id" => null,
        ];

        $device->load("area.hub.location");

        // If device is deployed to an area, get hierarchy from area
        if ($device->area_id && $device->area) {
            $hierarchy["area_id"] = $device->area->id;

            if ($device->area->hub) {
                $hierarchy["hub_id"] = $device->area->hub->id;

                if ($device->area->hub->location) {
                    $hierarchy["location_id"] =
                        $device->area->hub->location->id;
                }
            }
        }

        return $hierarchy;
    }
}
