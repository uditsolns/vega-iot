<?php

namespace App\Services\Audit\PDF;

use App\Models\AuditReport;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;

class AuditPdfGeneratorService
{
    /**
     * Generate PDF using Browsershot
     */
    public function generate(AuditReport $report, array $activities, array $resourceData): string
    {
        $formattedActivities = $this->formatActivities($activities, $report->type->value);

        $data = [
            'report' => $report,
            'resource' => $resourceData,
            'activities' => $formattedActivities,
            'generated_by' => $report->generatedBy->email,
        ];

        // Generate HTML
        $html = View::make('audit-reports.main', $data)->render();

        // Generate PDF with Browsershot
        return Browsershot::html($html)
            ->setOption('landscape', true)
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->timeout(120)
            ->setOption('args', ['--disable-gpu', '--no-sandbox'])
            ->pdf();
    }

    private function formatActivities(array $activities, string $reportType): array
    {
        $formatted = [];
        $srNo = 1;

        foreach ($activities as $activity) {
            $properties = $activity['properties'] ?? [];

            $formatted[] = [
                'sr_no' => $srNo++,
                'date_time' => $activity['created_at'],
                'module' => $this->getModuleName($activity),
                'action' => ucfirst(str_replace('_', ' ', $activity['event'] ?? 'action')),
                'description' => ucfirst($activity['description']) ?? '-',
                'properties' => $this->formatProperties($properties),
                'user' => $this->formatUser($activity['causer'] ?? null),
            ];
        }

        return $formatted;
    }

    private function formatUser(?array $causer): string
    {
        if (!$causer) {
            return '-';
        }

        $name = trim(($causer['first_name'] ?? '') . ' ' . ($causer['last_name'] ?? ''));
        $email = $causer['email'] ?? '';

        if ($name && $email) {
            return "{$name} ({$email})";
        }

        return $email ?: $name ?: '-';
    }

    private function formatProperties(array $properties): string
    {
        if (empty($properties)) {
            return '-';
        }

        $formatted = [];

        foreach ($properties as $key => $value) {
            if ($key === 'attributes' || $key === 'old') {
                $formatted[] = $this->formatNestedProperties($key, $value);
            } else {
                $formattedKey = $this->formatFieldName($key);
                $formattedValue = $this->formatValue($value);
                $formatted[] = "{$formattedKey}: {$formattedValue}";
            }
        }

        return !empty($formatted) ? implode('; ', $formatted) : '-';
    }

    private function formatNestedProperties(string $parentKey, $value): string
    {
        if (!is_array($value)) {
            return $this->formatFieldName($parentKey) . ': ' . $this->formatValue($value);
        }

        $items = [];
        foreach ($value as $k => $v) {
            $formattedKey = $this->formatFieldName($k);
            $formattedValue = $this->formatValue($v);
            $items[] = "{$formattedKey}={$formattedValue}";
        }

        $parentKeyFormatted = ucfirst($parentKey);
        return "{$parentKeyFormatted}[" . implode(', ', $items) . "]";
    }

    private function formatFieldName(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    private function formatValue($value): string
    {
        if ($value === null) {
            return '-';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            $items = [];
            foreach ($value as $k => $v) {
                if (is_numeric($k)) {
                    $items[] = $this->formatValue($v);
                } else {
                    $items[] = $k . '=' . $this->formatValue($v);
                }
            }
            return '{' . implode(', ', $items) . '}';
        }

        return (string) $value;
    }

    private function getModuleName(array $activity): string
    {
        $logName = $activity['log_name'] ?? 'system';
        return ucfirst($logName);
    }
}
