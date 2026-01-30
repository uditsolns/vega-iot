<?php

namespace App\Services\Report;

use App\Enums\ReportDataFormation;
use App\Enums\ReportFileType;
use App\Models\Device;
use App\Models\DeviceReading;
use App\Models\Report;
use App\Services\Report\PDF\PdfGeneratorService;
use Exception;
use Mpdf\MpdfException;

readonly class ReportGeneratorService
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    /**
     * Generate report based on type and format
     *
     * @param Report $report
     * @return string Path to generated file
     * @throws Exception
     */
    public function generate(Report $report): string
    {
        // Load device with necessary relationships
        $device = Device::with([
            'company',
            'area.hub.location',
            'currentConfiguration'
        ])->findOrFail($report->device_id);

        // Fetch readings data
        $readingsData = $this->fetchReadingsData($report, $device);

        // Determine generation method based on file type
        return match ($report->file_type) {
            ReportFileType::Pdf => $this->generatePdf($report, $device, $readingsData),
            ReportFileType::Csv => $this->generateCsv($report, $device, $readingsData),
        };
    }

    /**
     * Generate PDF report
     * @throws MpdfException
     */
    private function generatePdf(Report $report, Device $device, array $readingsData): string
    {
        return $this->pdfGenerator->generate(
            report: $report,
            device: $device,
            readingsData: $readingsData
        );
    }

    /**
     * Generate CSV report (future implementation)
     * @throws Exception
     */
    private function generateCsv(Report $report, Device $device, array $readingsData): string
    {
        // TODO: Implement CSV generation in Phase 2
        throw new Exception('CSV generation not yet implemented');
    }

    /**
     * Fetch readings data based on report parameters
     */
    private function fetchReadingsData(Report $report, Device $device): array
    {
        $query = DeviceReading::where('device_id', $device->id)
            ->whereBetween('recorded_at', [
                $report->from_datetime,
                $report->to_datetime
            ])
            ->orderBy('recorded_at');

        // Get all readings
        $readings = $query->get();

        // Format data based on device type and data formation
        return $this->formatReadingsData($readings, $device, $report);
    }

    /**
     * Format readings data based on device type and data formation enum
     */
    private function formatReadingsData($readings, Device $device, Report $report): array
    {
        $formattedData = [
            'logs' => [],
            'device_info' => $this->getDeviceInfo($device),
            'report_info' => $this->getReportInfo($report),
        ];

        foreach ($readings as $reading) {
            $log = [
                'timestamp' => $reading->recorded_at->format('d-m-Y H:i:s'),
            ];

            // Add data based on device type and data formation
            switch ($report->data_formation) {
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

        // Calculate statistics
        $formattedData['statistics'] = $this->calculateStatistics($readings, $report->data_formation);

        return $formattedData;
    }

    /**
     * Get device information for report
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
     * Get report configuration information
     */
    private function getReportInfo(Report $report): array
    {
        $config = $report->device->currentConfiguration;

        return [
            'report_name' => $report->name,
            'start_dt' => $report->from_datetime->format('d-m-Y H:i:s'),
            'end_dt' => $report->to_datetime->format('d-m-Y H:i:s'),
            'data_formation' => $report->data_formation->value,
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
     * Calculate statistics from readings
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

            // Calculate MKT (Mean Kinetic Temperature)
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

        // Temperature Probe statistics
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
