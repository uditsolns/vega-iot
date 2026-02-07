<?php

namespace App\Services\Report\PDF;

use App\DTOs\ReportGenerationDTO;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Exceptions\CouldNotTakeBrowsershot;

class PdfGeneratorService
{
    /**
     * Generate PDF using Browsershot + Chart.js
     */
    public function generate(
        ReportGenerationDTO $reportDto,
        array $readingsData
    ): string {
        // Merge data
        $data = array_merge(
            $readingsData['device_info'],
            $readingsData['report_info'],
            $readingsData['statistics']
        );
        $data['logs'] = $readingsData['logs'];
        $data['user_name'] = auth()->user()?->email ?? 'System';
        $data['data_formation'] = $reportDto->dataFormation->value;

        // Generate HTML based on format
        $html = match ($reportDto->format->value) {
            'graphical' => View::make('reports.pdf.graphical', compact('data'))->render(),
            'tabular' => View::make('reports.pdf.tabular', compact('data'))->render(),
            default => View::make('reports.pdf.graphical-tabular', compact('data'))->render(),
        };

        // Generate PDF with Browsershot
        return Browsershot::html($html)
            ->setOption('landscape', false)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->setOption('args', ['--disable-gpu', '--no-sandbox'])
            ->pdf();
    }
}
