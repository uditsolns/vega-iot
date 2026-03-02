<?php

namespace App\Http\Resources;

use App\Models\SensorReading;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SensorReading */
class SensorReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'recorded_at' => $this->recorded_at?->toISOString(),
            'received_at' => $this->received_at?->toISOString(),
            'value'       => $this->resolveValue(),

            // Metadata is stored on the first sensor reading per batch only
            // (battery, RSSI etc.) — will be null for all other sensors in the batch
            'metadata'    => $this->metadata,
        ];
    }

    /**
     * Return the appropriate typed value based on the loaded sensor type.
     *
     * value_point comes back from PostgreSQL as the string "(lng,lat)".
     * We parse it here so the API always returns a clean, typed structure.
     */
    private function resolveValue(): array|null|float
    {
        // GPS / point sensor
        if ($this->value_point !== null) {
            return $this->parsePoint($this->value_point);
        }

        return $this->value_numeric ? (float) $this->value_numeric : null;
    }

    /**
     * Parse a PostgreSQL POINT string "(longitude,latitude)" into an array.
     * PostgreSQL POINT convention is (x, y) => (longitude, latitude).
     */
    private function parsePoint(string $point): array
    {
        // Strip surrounding parens: "(103.8198,1.3521)" => "103.8198,1.3521"
        $clean   = trim($point, '()');
        $parts   = explode(',', $clean);

        return [
            'longitude' => isset($parts[0]) ? (float) $parts[0] : null,
            'latitude'  => isset($parts[1]) ? (float) $parts[1] : null,
        ];
    }
}
