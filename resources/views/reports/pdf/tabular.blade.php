@extends('reports.pdf.layouts.base')

@section('content')

    @include('reports.pdf.layouts.header')

    {{-- Device Info Section --}}
    @include('reports.pdf.components.device-info')

    {{-- Logger Summary Section --}}
    @include('reports.pdf.components.logger-summary')

    {{-- Observed Statistics Section --}}
    @include('reports.pdf.components.statistics')

    {{-- Determine which columns to show based on device type --}}
    @php
        $dataFormation = $data['data_formation'] ?? 'single_temperature';
        $logs = $data['logs'];

        $columns = [];

        // Determine columns based on data formation
        if (in_array($dataFormation, ['single_temperature', 'combined_temperature_humidity', 'separate_temperature_humidity', 'combined_probe_temperature', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'temperature';
        }

        if (in_array($dataFormation, ['combined_probe_temperature', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'tempprobe';
        }

        if (in_array($dataFormation, ['combined_temperature_humidity', 'separate_temperature_humidity', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'humidity';
        }

        // Add alarm column if needed
        if (isset($data['show_alarm']) && $data['show_alarm']) {
            $columns[] = 'alarm';
        }
    @endphp

    {{-- Data Table Section --}}
    @include('reports.pdf.components.data-table', [
        'logs' => $logs,
        'columns' => $columns,
        'data' => $data,
    ])

    @include('reports.pdf.layouts.footer')

@endsection
