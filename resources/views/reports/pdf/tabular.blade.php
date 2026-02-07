@extends('reports.pdf.layouts.base')

@section('content')
    @include('reports.pdf.components.device-info')
    @include('reports.pdf.components.logger-summary')
    @include('reports.pdf.components.statistics')

    @php
        $dataFormation = $data['data_formation'] ?? 'single_temperature';
        $logs = $data['logs'];
        $columns = [];

        if (in_array($dataFormation, ['single_temperature', 'combined_temperature_humidity', 'separate_temperature_humidity', 'combined_probe_temperature', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'temperature';
        }

        if (in_array($dataFormation, ['combined_probe_temperature', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'tempprobe';
        }

        if (in_array($dataFormation, ['combined_temperature_humidity', 'separate_temperature_humidity', 'combined_probe_temperature_humidity'])) {
            $columns[] = 'humidity';
        }
    @endphp

    @include('reports.pdf.components.data-table', [
        'logs' => $logs,
        'columns' => $columns,
        'data' => $data,
    ])
@endsection
