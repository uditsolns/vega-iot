<?php

namespace App\Services\Report\PDF;

use App\DTOs\ReportGenerationDTO;
use App\Enums\ReportFormat;
use App\Models\Device;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;
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
    public function generate(
        ReportGenerationDTO $reportDto,
        Device $device,
        array $readingsData
    ): string {
        // Merge all data for template
        $data = array_merge(
            $readingsData['device_info'],
            $readingsData['report_info'],
            $readingsData['statistics']
        );
        $data['logs'] = $readingsData['logs'];
        $data['user_name'] = auth()->user()?->email ?? 'System';

        // Generate PDF based on format
        match ($reportDto->format) {
            ReportFormat::Graphical => $this->generateGraphical($data, $reportDto),
            ReportFormat::Tabular => $this->generateTabular($data, $reportDto),
            ReportFormat::Both => $this->generateBoth($data, $reportDto),
        };

        return $this->mpdf->Output('', 'S');
    }

    /**
     * Generate graphical report (device info + charts)
     * @throws MpdfException
     */
    private function generateGraphical(array $data, ReportGenerationDTO $reportDto): void
    {
        $html = View::make('reports.pdf.graphical', [
            'data' => $data,
            'report' => $reportDto
        ])->render();
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Generate tabular report (device info + data table)
     * @throws MpdfException
     */
    private function generateTabular(array $data, ReportGenerationDTO $reportDto): void
    {
        $html = View::make('reports.pdf.tabular', [
            'data' => $data,
            'report' => $reportDto
        ])->render();
        $this->mpdf->WriteHTML($html);
    }

    /**
     * Generate combined report (device info + charts + table)
     * @throws MpdfException
     */
    private function generateBoth(array $data, ReportGenerationDTO $reportDto): void
    {
        $html = View::make('reports.pdf.graphical-tabular', [
            'data' => $data,
            'report' => $reportDto
        ])->render();
        $this->mpdf->WriteHTML($html);
    }
}
