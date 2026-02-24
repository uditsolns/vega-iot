<?php

namespace App\Services\Report\PDF;

use App\DTOs\ReportGenerationDTO;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class PdfGeneratorService
{
    public function generate(ReportGenerationDTO $dto, array $reportData): string
    {
        $view = match ($dto->format->value) {
            'graphical' => 'reports.pdf.graphical',
            'tabular'   => 'reports.pdf.tabular',
            default     => 'reports.pdf.graphical-tabular',
        };

        $html = View::make($view, ['data' => $reportData])->render();

        return Browsershot::html($html)
            ->setChromePath(env("CHROME_PATH"))
            ->setOption('landscape', false)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->setEnvironmentOptions([
                'CHROME_CONFIG_HOME' => storage_path('app/chrome/.config')
            ])
            ->setOption('args', ['--disable-gpu', '--no-sandbox'])
            ->pdf();
    }
}
