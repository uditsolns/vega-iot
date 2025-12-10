<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AggregatedReadingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Handle both stdClass objects and arrays
        $data = is_array($this->resource) ? $this->resource : (array) $this->resource;

        return [
            'time_bucket' => $data['bucket'] ?? null,
            'reading_count' => isset($data['reading_count']) ? (int) $data['reading_count'] : null,
            'sample_count' => isset($data['reading_count']) ? (int) $data['reading_count'] : null, // Alias

            // Device information (for multi-device aggregations)
            'device_id' => $data['device_id'] ?? null,
            'active_devices' => isset($data['active_devices']) ? (int) $data['active_devices'] : null,
            'total_readings' => isset($data['total_readings']) ? (int) $data['total_readings'] : null,

            // Temperature aggregates
            'temperature' => $this->when(
                isset($data['avg_temp']) || isset($data['min_temp']) || isset($data['max_temp']),
                [
                    'avg' => isset($data['avg_temp']) ? (float) $data['avg_temp'] : null,
                    'min' => isset($data['min_temp']) ? (float) $data['min_temp'] : null,
                    'max' => isset($data['max_temp']) ? (float) $data['max_temp'] : null,
                    'stddev' => isset($data['stddev_temp']) ? (float) $data['stddev_temp'] : null,
                ]
            ),

            // Humidity aggregates
            'humidity' => $this->when(
                isset($data['avg_humidity']) || isset($data['min_humidity']) || isset($data['max_humidity']),
                [
                    'avg' => isset($data['avg_humidity']) ? (float) $data['avg_humidity'] : null,
                    'min' => isset($data['min_humidity']) ? (float) $data['min_humidity'] : null,
                    'max' => isset($data['max_humidity']) ? (float) $data['max_humidity'] : null,
                    'stddev' => isset($data['stddev_humidity']) ? (float) $data['stddev_humidity'] : null,
                ]
            ),

            // Temperature probe aggregates
            'temp_probe' => $this->when(
                isset($data['avg_temp_probe']) || isset($data['min_temp_probe']) || isset($data['max_temp_probe']),
                [
                    'avg' => isset($data['avg_temp_probe']) ? (float) $data['avg_temp_probe'] : null,
                    'min' => isset($data['min_temp_probe']) ? (float) $data['min_temp_probe'] : null,
                    'max' => isset($data['max_temp_probe']) ? (float) $data['max_temp_probe'] : null,
                    'stddev' => isset($data['stddev_temp_probe']) ? (float) $data['stddev_temp_probe'] : null,
                ]
            ),

            // Battery aggregates
            'battery' => $this->when(
                isset($data['avg_battery']) || isset($data['min_battery']),
                [
                    'avg' => isset($data['avg_battery']) ? (float) $data['avg_battery'] : null,
                    'min' => isset($data['min_battery']) ? (float) $data['min_battery'] : null,
                ]
            ),

            // WiFi signal aggregates
            'wifi_signal' => $this->when(
                isset($data['avg_wifi_signal']),
                [
                    'avg' => isset($data['avg_wifi_signal']) ? (float) $data['avg_wifi_signal'] : null,
                ]
            ),
        ];
    }
}
