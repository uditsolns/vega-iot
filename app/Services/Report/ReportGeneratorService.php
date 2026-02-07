<?php

namespace App\Services\Report;

use App\Contracts\ReportableInterface;
use App\DTOs\ReportGenerationDTO;
use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Services\Report\PDF\PdfGeneratorService;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

readonly class ReportGeneratorService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Generate report from a Reportable entity
     */
    public function generateFromReportable(ReportableInterface $reportable): string
    {
        $dto = ReportGenerationDTO::fromReportable($reportable);
        return $this->generate($dto);
    }

    /**
     * Generate report from a DTO
     */
    public function generate(ReportGenerationDTO $dto): string
    {
        $device = Device::with([
            'company',
            'area.hub.location',
            'currentConfiguration'
        ])->findOrFail($dto->deviceId);

        $readingsData = $this->fetchReadingsData($dto, $device);

        return match ($dto->fileType) {
            ReportFileType::Pdf => $this->generatePdf($dto, $readingsData),
            ReportFileType::Csv => $this->generateCsv($dto, $readingsData),
        };
    }

    /**
     * Generate PDF report
     */
    private function generatePdf(
        ReportGenerationDTO $dto,
        array $readingsData
    ): string {
        return $this->pdfGenerator->generate(
            reportDto: $dto,
            readingsData: $readingsData
        );
    }

    /**
     * Generate CSV report
     * @throws Exception
     */
    private function generateCsv(
        ReportGenerationDTO $dto,
        array $readingsData
    ): string {
        throw new Exception('CSV generation not yet implemented');
    }

    /**
     * Fetch readings data - OPTIMIZED with TimescaleDB
     */
    private function fetchReadingsData(ReportGenerationDTO $dto, Device $device): array
    {
        // Use time_bucket for aggregation if interval-based sampling needed
        $intervalMinutes = $dto->interval;

        // For large datasets, use TimescaleDB time_bucket aggregation
        if ($this->shouldUseAggregation($dto)) {
            $readings = $this->fetchAggregatedReadings($dto, $device, $intervalMinutes);
        } else {
            // Direct query for smaller datasets
            $readings = DeviceReading::where('device_id', $device->id)
                ->whereBetween('recorded_at', [
                    $dto->fromDatetime,
                    $dto->toDatetime
                ])
                ->orderBy('recorded_at')
                ->get();
        }

        return $this->formatReadingsData($readings, $device, $dto);
    }

    /**
     * Check if aggregation should be used based on data volume
     */
    private function shouldUseAggregation(ReportGenerationDTO $dto): bool
    {
        $hours = $dto->fromDatetime->diffInHours($dto->toDatetime);
        $estimatedRecords = ($hours * 60) / 5; // Assuming 5-min default interval

        return $estimatedRecords > 10000; // Aggregate if > 10k records
    }

    /**
     * Fetch aggregated readings using TimescaleDB time_bucket
     */
    private function fetchAggregatedReadings(ReportGenerationDTO $dto, Device $device, int $intervalMinutes)
    {
        $bucketInterval = max($intervalMinutes, 15); // Minimum 15-min buckets

        return DB::table('device_readings')
            ->select([
                DB::raw("time_bucket('{$bucketInterval} minutes', recorded_at) as timestamp"),
                DB::raw('AVG(temperature) as temperature'),
                DB::raw('AVG(humidity) as humidity'),
                DB::raw('AVG(temp_probe) as temp_probe'),
            ])
            ->where('device_id', $device->id)
            ->whereBetween('recorded_at', [
                $dto->fromDatetime,
                $dto->toDatetime
            ])
            ->groupBy(DB::raw('1'))
            ->orderBy('timestamp')
            ->get()
            ->map(function ($row) {
                return (object) [
                    'recorded_at' => Carbon::parse($row->timestamp),
                    'temperature' => $row->temperature ? round($row->temperature, 2) : null,
                    'humidity' => $row->humidity ? round($row->humidity, 2) : null,
                    'temp_probe' => $row->temp_probe ? round($row->temp_probe, 2) : null,
                ];
            });
    }

    /**
     * Format readings data
     */
    private function formatReadingsData($readings, Device $device, ReportGenerationDTO $dto): array
    {
        $formattedData = [
            'logs' => [],
            'device_info' => $this->getDeviceInfo($device),
            'report_info' => $this->getReportInfo($dto, $device),
        ];

        foreach ($readings as $reading) {
            $log = [
                'timestamp' => $reading->recorded_at->format('d-m-Y H:i:s'),
            ];

            switch ($dto->dataFormation) {
                case ReportDataFormation::SingleTemperature:
                    $log['temperature'] = $reading->temperature;
                    break;

                case ReportDataFormation::SeparateTemperatureHumidity:
                case ReportDataFormation::CombinedTemperatureHumidity:
                    $log['temperature'] = $reading->temperature;
                    $log['humidity'] = $reading->humidity;
                    break;

                case ReportDataFormation::CombinedProbeTemperature:
                    $log['temperature'] = $reading->temperature;
                    $log['tempprobe'] = $reading->temp_probe;
                    break;

                case ReportDataFormation::CombinedProbeTemperatureHumidity:
                    $log['temperature'] = $reading->temperature;
                    $log['tempprobe'] = $reading->temp_probe;
                    $log['humidity'] = $reading->humidity;
                    break;
            }

            $formattedData['logs'][] = $log;
        }

        $formattedData['statistics'] = $this->calculateStatistics($readings, $dto->dataFormation);

        return $formattedData;
    }

    /**
     * Get device information
     */
    private function getDeviceInfo(Device $device): array
    {
        return [
            'make' => $device->make,
            'model' => $device->model,
            'serialno' => $device->device_uid,
            'device_name' => $device->device_name ?? $device->device_code,
            'device_code' => $device->device_code,
            'instrumentid' => $device->id,
            'temp_res' => $device->temp_resolution,
            'temp_acc' => $device->temp_accuracy,
            'hum_res' => $device->humidity_resolution,
            'hum_acc' => $device->humidity_accuracy,
            'tempprobe_res' => $device->temp_probe_resolution,
            'tempprobe_acc' => $device->temp_probe_accuracy,
        ];
    }

    /**
     * Get report configuration
     */
    private function getReportInfo(ReportGenerationDTO $dto, Device $device): array
    {
        $config = $device->currentConfiguration;

        return [
            'report_name' => $dto->reportName,
            'start_dt' => $dto->fromDatetime->format('d-m-Y H:i:s'),
            'end_dt' => $dto->toDatetime->format('d-m-Y H:i:s'),
            'data_formation' => $dto->dataFormation->value,
            'record_interval' => $config->record_interval ?? 5,
            'sending_interval' => $config->send_interval ?? 15,
            'min_temp' => $config->temp_min_critical ?? 20,
            'max_temp' => $config->temp_max_critical ?? 50,
            'min_warn_temp' => $config->temp_min_warning ?? 25,
            'max_warn_temp' => $config->temp_max_warning ?? 45,
            'min_hum' => $config->humidity_min_critical ?? 40,
            'max_hum' => $config->humidity_max_critical ?? 90,
            'min_warn_hum' => $config->humidity_min_warning ?? 50,
            'max_warn_hum' => $config->humidity_max_warning ?? 80,
            'min_tempprobe' => $config->temp_probe_min_critical,
            'max_tempprobe' => $config->temp_probe_max_critical,
            'min_warn_tempProbe' => $config->temp_probe_min_warning,
            'max_Warn_tempProbe' => $config->temp_probe_max_warning,
        ];
    }

    /**
     * Calculate statistics
     */
    private function calculateStatistics($readings, ReportDataFormation $dataFormation): array
    {
        $stats = [];

        if ($readings->isEmpty()) {
            return $stats;
        }

        // Temperature statistics
        if (in_array($dataFormation, [
            ReportDataFormation::SingleTemperature,
            ReportDataFormation::CombinedTemperatureHumidity,
            ReportDataFormation::SeparateTemperatureHumidity,
            ReportDataFormation::CombinedProbeTemperature,
            ReportDataFormation::CombinedProbeTemperatureHumidity,
        ])) {
            $temps = $readings->pluck('temperature')->filter();
            $stats['minTempData'] = $temps->min();
            $stats['maxTempData'] = $temps->max();
            $stats['avgTemp'] = round($temps->avg(), 2);
            $stats['mkt'] = $this->calculateMKT($temps->toArray());
        }

        // Humidity statistics
        if (in_array($dataFormation, [
            ReportDataFormation::CombinedTemperatureHumidity,
            ReportDataFormation::SeparateTemperatureHumidity,
            ReportDataFormation::CombinedProbeTemperatureHumidity,
        ])) {
            $humidity = $readings->pluck('humidity')->filter();
            $stats['minHumData'] = $humidity->min();
            $stats['maxHumData'] = $humidity->max();
            $stats['avgHum'] = round($humidity->avg(), 2);
        }

        // Temp Probe statistics
        if (in_array($dataFormation, [
            ReportDataFormation::CombinedProbeTemperature,
            ReportDataFormation::CombinedProbeTemperatureHumidity,
        ])) {
            $tempProbe = $readings->pluck('temp_probe')->filter();
            $stats['minTempProbeData'] = $tempProbe->min();
            $stats['maxTempProbeData'] = $tempProbe->max();
            $stats['avgTempProbe'] = round($tempProbe->avg(), 2);
        }

        return $stats;
    }

    /**
     * Calculate Mean Kinetic Temperature (MKT)
     * Formula: MKT = (ΔH/R) / ln[(e^(ΔH/RT1) + e^(ΔH/RT2) + ... + e^(ΔH/RTn)) / n]
     */
    private function calculateMKT(array $temperatures): float
    {
        if (empty($temperatures)) {
            return 0;
        }

        $deltaH = 83.144; // Activation energy (kJ/mol)
        $R = 0.008314; // Gas constant (kJ/mol·K)

        $sumExp = 0;
        $count = count($temperatures);

        foreach ($temperatures as $temp) {
            $tempKelvin = $temp + 273.15;
            $sumExp += exp(-$deltaH / ($R * $tempKelvin));
        }

        $mktKelvin = -$deltaH / ($R * log($sumExp / $count));
        $mktCelsius = $mktKelvin - 273.15;

        return round($mktCelsius, 2);
    }
}
