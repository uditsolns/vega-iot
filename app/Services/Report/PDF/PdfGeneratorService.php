<?php

namespace App\Services\Report\PDF;

use App\Enums\ReportFormat;
use App\Models\Device;
use App\Models\Report;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\MpdfException;

class PdfGeneratorService
{
    private Mpdf $mpdf;

    /**
     * @throws MpdfException
     */
    public function __construct()
    {
        // Initialize mPDF with settings
        $this->mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 25,
            'margin_bottom' => 20,
            'margin_header' => 9,
            'margin_footer' => 9,
            'tempDir' => storage_path('app/temp'),
        ]);
    }

    /**
     * Generate PDF based on report format
     * @throws MpdfException
     */
    public function generate(Report $report, Device $device, array $readingsData): string
    {
        // Merge all data for template
        $data = array_merge($readingsData['device_info'], $readingsData['report_info'], $readingsData['statistics']);
        $data['logs'] = $readingsData['logs'];
        $data['user_name'] = $report->generatedBy->email;

        // Generate PDF based on format
        match ($report->format) {
            ReportFormat::Graphical => $this->generateGraphical($data, $report),
            ReportFormat::Tabular => $this->generateTabular($data, $report),
            ReportFormat::Both => $this->generateBoth($data, $report),
        };

        return $this->mpdf->Output('', 'S');
    }

    /**
     * Generate graphical report (device info + charts)
     * @throws MpdfException
     */
    private function generateGraphical(array $data, Report $report): void
    {
        $html = View::make('reports.pdf.graphical', compact('data', 'report'))->render();
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Generate tabular report (device info + data table)
     * @throws MpdfException
     */
    private function generateTabular(array $data, Report $report): void
    {
        $html = View::make('reports.pdf.tabular', compact('data', 'report'))->render();
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Generate combined report (device info + charts + table)
     * @throws MpdfException
     */
    private function generateBoth(array $data, Report $report): void
    {
        $html = View::make('reports.pdf.graphical-tabular', compact('data', 'report'))->render();
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Generate unique filename for PDF
     */
    private function generateFilename(Report $report): string
    {
        return sprintf(
            'report_%s_%s_%s.pdf',
            $report->id,
            $report->device->device_code,
            now()->format('YmdHis')
        );
    }
}
