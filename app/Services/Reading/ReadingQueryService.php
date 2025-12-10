<?php

namespace App\Services\Reading;

use App\Models\Area;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ReadingQueryService
{
    /**
     * List readings with filters, sorting, and pagination
     */
    public function list(array $filters, User $user): Paginator
    {
        return QueryBuilder::for(DeviceReading::forUser($user))
            ->allowedFilters([
                AllowedFilter::exact("device_id"),
                AllowedFilter::exact("company_id"),
                AllowedFilter::exact("location_id"),
                AllowedFilter::exact("hub_id"),
                AllowedFilter::exact("area_id"),
                AllowedFilter::callback("from", function ($query, $value) {
                    $query->where("recorded_at", ">=", $value);
                }),
                AllowedFilter::callback("to", function ($query, $value) {
                    $query->where("recorded_at", "<=", $value);
                }),
                AllowedFilter::callback("date", function ($query, $value) {
                    $date = Carbon::parse($value);
                    $query->whereDate("recorded_at", $date);
                }),
            ])
            ->allowedSorts([
                "recorded_at",
                "temperature",
                "humidity",
                "temp_probe",
                "battery_percentage",
            ])
            ->defaultSort("-recorded_at")
            ->simplePaginate($filters["per_page"] ?? 50);
    }

    /**
     * Get readings for a specific device
     */
    public function getDeviceReadings(
        Device $device,
        array $filters,
        User $user
    ): Paginator {
        $query = DeviceReading::forUser($user)->forDevice($device->id);

        // Apply time filters
        if (isset($filters["from"])) {
            $query->where("recorded_at", ">=", $filters["from"]);
        }

        if (isset($filters["to"])) {
            $query->where("recorded_at", "<=", $filters["to"]);
        }

        // Apply date filter
        if (isset($filters["date"])) {
            $date = Carbon::parse($filters["date"]);
            $query->whereDate("recorded_at", $date);
        }

        return $query
            ->latest()
            ->simplePaginate($filters["per_page"] ?? 50);
    }

    /**
     * Get the latest reading for a device
     */
    public function getLatestReading(Device $device, User $user): ?DeviceReading
    {
        return DeviceReading::forUser($user)
            ->forDevice($device->id)
            ->latest()
            ->first();
    }

    /**
     * Get readings for all devices in an area
     */
    public function getAreaReadings(
        Area $area,
        array $filters,
        User $user
    ): Paginator {
        $query = DeviceReading::forUser($user)->forArea($area->id);

        // Apply time filters
        if (isset($filters["from"])) {
            $query->where("recorded_at", ">=", $filters["from"]);
        }

        if (isset($filters["to"])) {
            $query->where("recorded_at", "<=", $filters["to"]);
        }

        // Apply date filter
        if (isset($filters["date"])) {
            $date = Carbon::parse($filters["date"]);
            $query->whereDate("recorded_at", $date);
        }

        // Apply device_id filter if specified
        if (isset($filters["device_id"])) {
            $query->where("device_id", $filters["device_id"]);
        }

        return $query
            ->latest()
            ->simplePaginate($filters["per_page"] ?? 50);
    }

    /**
     * Get aggregated readings data
     */
    public function aggregations(array $filters, User $user): array
    {
        $query = DeviceReading::forUser($user);

        // Apply filters
        if (isset($filters["device_id"])) {
            $query->where("device_id", $filters["device_id"]);
        }

        if (isset($filters["area_id"])) {
            $query->where("area_id", $filters["area_id"]);
        }

        if (isset($filters["location_id"])) {
            $query->where("location_id", $filters["location_id"]);
        }

        if (isset($filters["hub_id"])) {
            $query->where("hub_id", $filters["hub_id"]);
        }

        // Time range is required for aggregations
        $from = $filters["from"] ?? Carbon::now()->subDays(7);
        $to = $filters["to"] ?? Carbon::now();

        $query->where("recorded_at", ">=", $from)
            ->where("recorded_at", "<=", $to);

        // Determine aggregation interval
        $interval = $filters["interval"] ?? "1 hour";

        // Build aggregation query
        $aggregations = $query
            ->select([
                DB::raw(
                    "time_bucket('{$interval}', recorded_at) as time_bucket"
                ),
                DB::raw("AVG(temperature) as avg_temperature"),
                DB::raw("MIN(temperature) as min_temperature"),
                DB::raw("MAX(temperature) as max_temperature"),
                DB::raw("AVG(humidity) as avg_humidity"),
                DB::raw("MIN(humidity) as min_humidity"),
                DB::raw("MAX(humidity) as max_humidity"),
                DB::raw("AVG(temp_probe) as avg_temp_probe"),
                DB::raw("MIN(temp_probe) as min_temp_probe"),
                DB::raw("MAX(temp_probe) as max_temp_probe"),
                DB::raw("COUNT(*) as reading_count"),
            ])
            ->groupBy("time_bucket")
            ->orderBy("time_bucket", "desc")
            ->get();

        return [
            "interval" => $interval,
            "from" => $from,
            "to" => $to,
            "aggregations" => $aggregations,
        ];
    }

    /**
     * Get statistics for readings
     */
    public function getStatistics(array $filters, User $user): array
    {
        $query = DeviceReading::forUser($user);

        // Apply filters
        if (isset($filters["device_id"])) {
            $query->where("device_id", $filters["device_id"]);
        }

        if (isset($filters["area_id"])) {
            $query->where("area_id", $filters["area_id"]);
        }

        // Time range
        $from = $filters["from"] ?? Carbon::now()->subDays(7);
        $to = $filters["to"] ?? Carbon::now();

        $query->where("recorded_at", ">=", $from)
            ->where("recorded_at", "<=", $to);

        $stats = $query
            ->select([
                DB::raw("COUNT(*) as total_readings"),
                DB::raw("AVG(temperature) as avg_temperature"),
                DB::raw("MIN(temperature) as min_temperature"),
                DB::raw("MAX(temperature) as max_temperature"),
                DB::raw("AVG(humidity) as avg_humidity"),
                DB::raw("MIN(humidity) as min_humidity"),
                DB::raw("MAX(humidity) as max_humidity"),
                DB::raw("AVG(temp_probe) as avg_temp_probe"),
                DB::raw("MIN(temp_probe) as min_temp_probe"),
                DB::raw("MAX(temp_probe) as max_temp_probe"),
                DB::raw("AVG(battery_percentage) as avg_battery"),
                DB::raw("MIN(battery_percentage) as min_battery"),
            ])
            ->first();

        return [
            "from" => $from,
            "to" => $to,
            "statistics" => $stats,
        ];
    }
}
