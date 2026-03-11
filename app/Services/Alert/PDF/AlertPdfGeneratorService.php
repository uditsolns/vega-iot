<?php

namespace App\Services\Alert\PDF;

use App\Enums\AlertSeverity;
use App\Enums\AlertStatus;
use App\Models\Alert;
use App\Models\SensorConfiguration;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class AlertPdfGeneratorService
{
    public function generate(Alert $alert): string
    {
        $alert->loadMissing([
            'device.area.hub.location',
            'deviceSensor.sensorType',
            'acknowledgedBy',
            'resolvedBy',
        ]);

        $configAtAlertTime = $this->resolveConfigAtAlertTime($alert);

        $html = View::make('alerts.report', [
            'alert'           => $alert,
            'locationPath'    => $this->resolveLocationPath($alert),
            'operatingRange'  => $this->resolveOperatingRange($alert, $configAtAlertTime),
            'showAcknowledgedBy' => $alert->acknowledged_at !== null,
            'showResolvedBy'     => $alert->status === AlertStatus::Resolved,
        ])->render();

        return Browsershot::html($html)
            ->setChromePath(env("CHROME_PATH"))
            ->format('A4')
            ->margins(12, 12, 18, 12)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(60)
            ->setEnvironmentOptions([
                'CHROME_CONFIG_HOME' => storage_path('app/chrome/.config')
            ])
            ->setOption('args', ['--disable-gpu', '--no-sandbox'])
            ->pdf();
    }

    // ─── Config resolution ────────────────────────────────────────────────────

    private function resolveConfigAtAlertTime(Alert $alert): ?SensorConfiguration
    {
        if (!$alert->deviceSensor || !$alert->started_at) {
            return null;
        }

        return SensorConfiguration::where('device_sensor_id', $alert->device_sensor_id)
            ->where('effective_from', '<=', $alert->started_at)
            ->where(function ($q) use ($alert) {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $alert->started_at);
            })
            ->orderByDesc('effective_from')
            ->first();
    }

    // ─── Operating range ──────────────────────────────────────────────────────

    private function resolveOperatingRange(Alert $alert, ?SensorConfiguration $config): string
    {
        if (!$config) {
            return '—';
        }

        $unit = $alert->sensor_unit ?? '';

        [$min, $max] = match ($alert->severity) {
            AlertSeverity::Critical => [
                $config->min_critical ?? $config->min_warning,
                $config->max_critical ?? $config->max_warning,
            ],
            AlertSeverity::Warning => [
                $config->min_warning ?? $config->min_critical,
                $config->max_warning ?? $config->max_critical,
            ],
        };

        if ($min === null && $max === null) {
            return '—';
        }

        $minPart = $min !== null ? (float) $min : '—';
        $maxPart = $max !== null ? (float) $max : '—';

        return "{$minPart} - {$maxPart} {$unit}";
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveLocationPath(Alert $alert): string
    {
        $area     = $alert->device->area;
        $hub      = $area?->hub;
        $location = $hub?->location;

        if (!$area || !$hub || !$location) {
            return 'Unassigned';
        }

        return "{$location->name} → {$hub->name} → {$area->name}";
    }
}
