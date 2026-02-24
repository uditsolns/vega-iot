<?php

namespace App\Services\Report;

use App\Contracts\ReportableInterface;
use App\DTOs\ReportGenerationDTO;
use App\Enums\ReportFileType;
use App\Models\Device;
use App\Models\DeviceSensor;
use App\Services\Report\PDF\PdfGeneratorService;
use Illuminate\Support\Collection;

readonly class ReportGeneratorService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator,
        private ReportQueryService  $queryService,
    ) {}

    // ─── Public Entry Points ─────────────────────────────────────────────────

    public function generateFromReportable(ReportableInterface $reportable): string
    {
        return $this->generate(ReportGenerationDTO::fromReportable($reportable));
    }

    public function generate(ReportGenerationDTO $dto): string
    {
        // 1. Load device with eager-loaded sensor metadata
        $device = Device::with([
            'company',
            'area.hub.location',
            'currentConfiguration',
        ])->findOrFail($dto->deviceId);

        // 2. Load the specific DeviceSensor models selected for the report
        //    (ordered by slot_number for consistent column ordering)
        $sensors = DeviceSensor::with(['sensorType', 'currentConfiguration'])
            ->whereIn('id', $dto->sensorIds)
            ->where('device_id', $dto->deviceId)
            ->orderBy('slot_number')
            ->get();

        if ($sensors->isEmpty()) {
            throw new \RuntimeException('No valid sensors found for the report.');
        }

        // Use the ordered sensor IDs (matching slot order, not request order)
        $orderedSensorIds = $sensors->pluck('id')->toArray();

        // 3. Run both DB queries in parallel conceptually (sequential in PHP,
        //    but each is a single optimized TimescaleDB pass)
        $bucketedRows = $this->queryService->getBucketedReadings($dto, $orderedSensorIds);
        $statsMap     = $this->queryService->getSensorStatistics($dto, $orderedSensorIds);

        // 4. Build the structured data payload for the PDF renderer
        $reportData = $this->buildReportData($dto, $device, $sensors, $bucketedRows, $statsMap);

        // 5. Render
        return match ($dto->fileType) {
            ReportFileType::Pdf => $this->pdfGenerator->generate($dto, $reportData),
            ReportFileType::Csv => $this->generateCsv($dto, $reportData),
        };
    }

    // ─── Data Assembly ────────────────────────────────────────────────────────

    /**
     * Assembles the full data structure passed to the Blade PDF templates.
     */
    private function buildReportData(
        ReportGenerationDTO $dto,
        Device              $device,
        Collection          $sensors,
        Collection          $bucketedRows,
        Collection          $statsMap,
    ): array {
        $sensorMeta = $this->buildSensorMeta($sensors, $statsMap);

        return [
            'report_name'  => $dto->reportName,
            'user_name'    => auth()->user()?->name ?? 'System',
            'start_dt'     => $dto->fromDatetime->format('d-m-Y H:i:s'),
            'end_dt'       => $dto->toDatetime->format('d-m-Y H:i:s'),
            'interval'     => $dto->interval,

            'device'       => $this->buildDeviceInfo($device),
            'logger'       => $this->buildLoggerInfo($device, $dto),
            'sensors'      => $sensorMeta,
            'statistics'   => $this->buildStatistics($sensorMeta, $statsMap, $bucketedRows),
            'logs'         => $this->buildLogs($bucketedRows, $sensors),
        ];
    }

    /**
     * Per-sensor metadata: label, unit, thresholds, accuracy/resolution.
     * Used by templates for column headers, chart axis labels, and threshold lines.
     */
    private function buildSensorMeta(Collection $sensors, Collection $statsMap): array
    {
        return $sensors->map(function (DeviceSensor $sensor) use ($statsMap) {
            $config = $sensor->currentConfiguration;
            $type   = $sensor->sensorType;

            return [
                'device_sensor_id' => $sensor->id,
                'key'              => "sensor_{$sensor->id}",   // column key in log rows
                'slot_number'      => $sensor->slot_number,
                'label'            => $sensor->label ?? $type->name,
                'unit'             => $type->unit,
                'data_type'        => $type->data_type->value,
                'supports_threshold' => $type->supports_threshold_config,
                'accuracy'         => $sensor->accuracy,
                'resolution'       => $sensor->resolution,

                // Thresholds from current SensorConfiguration
                'min_critical'     => $config?->min_critical,
                'max_critical'     => $config?->max_critical,
                'min_warning'      => $config?->min_warning,
                'max_warning'      => $config?->max_warning,
            ];
        })->values()->toArray();
    }

    /**
     * Log rows: one entry per time_bucket interval.
     * Format: ['timestamp' => '...', 'sensor_12' => 4.2, 'sensor_13' => 65.1, ...]
     */
    private function buildLogs(Collection $bucketedRows, Collection $sensors): array
    {
        return $bucketedRows->map(function ($row) use ($sensors) {
            $log = ['timestamp' => $row->bucket->format('d-m-Y H:i:s')];

            foreach ($sensors as $sensor) {
                $key        = "sensor_{$sensor->id}";
                $raw        = $row->{$key} ?? null;
                $log[$key]  = $raw !== null ? round((float) $raw, 2) : null;
            }

            return $log;
        })->toArray();
    }

    /**
     * Per-sensor statistics computed from stats_agg() results,
     * plus MKT (computed in PHP from bucketed averages).
     */
    private function buildStatistics(array $sensorMeta, Collection $statsMap, Collection $bucketedRows): array
    {
        $stats = [];

        foreach ($sensorMeta as $meta) {
            $sensorId = $meta['device_sensor_id'];
            $key      = $meta['key'];
            $dbStats  = $statsMap->get($sensorId);

            if (!$dbStats) {
                continue;
            }

            $sensorStats = [
                'key'        => $key,
                'label'      => $meta['label'],
                'unit'       => $meta['unit'],
                'min'        => $dbStats->min_val !== null ? round((float) $dbStats->min_val, 2) : null,
                'max'        => $dbStats->max_val !== null ? round((float) $dbStats->max_val, 2) : null,
                'avg'        => $dbStats->avg_val !== null ? round((float) $dbStats->avg_val, 2) : null,
                'stddev'     => $dbStats->stddev_val !== null ? round((float) $dbStats->stddev_val, 3) : null,
                'count'      => (int) ($dbStats->count_val ?? 0),
                'first_val'  => $dbStats->first_val !== null ? round((float) $dbStats->first_val, 2) : null,
                'last_val'   => $dbStats->last_val !== null ? round((float) $dbStats->last_val, 2) : null,
            ];

            // MKT only makes sense for temperature sensors (°C, °F, K)
            if (in_array(strtolower($meta['unit']), ['°c', 'c', 'celsius'])) {
                $bucketedValues = $bucketedRows
                    ->pluck($key)
                    ->filter(fn($v) => $v !== null)
                    ->map(fn($v) => (float) $v)
                    ->toArray();

                $sensorStats['mkt'] = $this->calculateMkt($bucketedValues);
            }

            $stats[] = $sensorStats;
        }

        return $stats;
    }

    // ─── Device / Logger Info ─────────────────────────────────────────────────

    private function buildDeviceInfo(Device $device): array
    {
        $model = $device->deviceModel ?? null;

        return [
            'id'           => $device->id,
            'device_code'  => $device->device_code,
            'device_name'  => $device->device_name ?? $device->device_code,
            'device_uid'   => $device->device_uid,
            'make'         => $model?->vendor?->label() ?? 'N/A',
            'model'        => $model?->model_name ?? 'N/A',
            'firmware'     => $device->firmware_version ?? 'N/A',
            'location'     => $device->getLocationPath(),
        ];
    }

    private function buildLoggerInfo(Device $device, ReportGenerationDTO $dto): array
    {
        $config = $device->currentConfiguration;

        return [
            'recording_interval' => $config?->recording_interval ?? 5,
            'sending_interval'   => $config?->sending_interval ?? 15,
            'interval'           => $dto->interval,
            'start_dt'           => $dto->fromDatetime->format('d-m-Y H:i:s'),
            'end_dt'             => $dto->toDatetime->format('d-m-Y H:i:s'),
        ];
    }

    // ─── CSV ──────────────────────────────────────────────────────────────────

    private function generateCsv(ReportGenerationDTO $dto, array $reportData): string
    {
        $sensors = $reportData['sensors'];
        $logs    = $reportData['logs'];

        $handle = fopen('php://temp', 'r+');

        // Header row: Timestamp, then one column per sensor (label + unit)
        $headers = ['Timestamp'];
        foreach ($sensors as $sensor) {
            $headers[] = "{$sensor['label']} ({$sensor['unit']})";
        }
        fputcsv($handle, $headers);

        // Data rows
        foreach ($logs as $log) {
            $row = [$log['timestamp']];
            foreach ($sensors as $sensor) {
                $row[] = $log[$sensor['key']] ?? '';
            }
            fputcsv($handle, $row);
        }

        // Statistics section
        fputcsv($handle, []);
        fputcsv($handle, ['--- Statistics ---']);
        fputcsv($handle, ['Sensor', 'Min', 'Max', 'Avg', 'Std Dev', 'MKT', 'Count']);

        foreach ($reportData['statistics'] as $stat) {
            fputcsv($handle, [
                $stat['label'],
                $stat['min'],
                $stat['max'],
                $stat['avg'],
                $stat['stddev'],
                $stat['mkt'] ?? 'N/A',
                $stat['count'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    // ─── MKT Calculation ─────────────────────────────────────────────────────

    /**
     * Mean Kinetic Temperature (MKT) — ICH Q1A pharmaceutical standard.
     *
     * Formula:  MKT = (ΔH/R) / ln[ Σ(e^(-ΔH/RTi)) / n ]
     *
     * Where:
     *   ΔH = 83,144 J/mol  (activation energy for degradation, ICH standard)
     *   R  = 8.314 J/mol·K (universal gas constant)
     *   Ti = temperature in Kelvin for each reading
     *
     * Must be done in PHP because it requires per-reading exp() which
     * cannot be efficiently expressed as a single-pass SQL aggregate.
     * We use the interval-bucketed averages as input (already aggregated by DB).
     */
    private function calculateMkt(array $temperatures): ?float
    {
        if (count($temperatures) < 2) {
            return null;
        }

        $deltaH = 83144.0; // J/mol
        $R      = 8.314;   // J/mol·K

        $sumExp = 0.0;
        $n      = count($temperatures);

        foreach ($temperatures as $tempC) {
            $tempK   = $tempC + 273.15;
            $sumExp += exp(-$deltaH / ($R * $tempK));
        }

        if ($sumExp <= 0) {
            return null;
        }

        $mktK = (-$deltaH / $R) / log($sumExp / $n);
        return round($mktK - 273.15, 2);
    }
}
