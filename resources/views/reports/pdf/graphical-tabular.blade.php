@extends('reports.pdf.layouts.base')

@section('content')
    @include('reports.pdf.components.device-info')
    @include('reports.pdf.components.logger-summary')
    @include('reports.pdf.components.statistics')

    <div class="section-title">Trend</div>

    @php
        $dataFormation = $data['data_formation'] ?? 'single_temperature';
        $logs = $data['logs'];
    @endphp

    @if($dataFormation === 'single_temperature')
        <div class="subsection-title">Temperature</div>
        @include('reports.pdf.components.chart-single', [
            'logs' => $logs,
            'dataKey' => 'temperature',
            'label' => 'Temperature (°C)',
            'color' => '#305CDE',
            'minThreshold' => $data['min_temp'] ?? null,
            'maxThreshold' => $data['max_temp'] ?? null,
        ])

    @elseif($dataFormation === 'combined_temperature_humidity')
        <div class="subsection-title">Temperature & Humidity</div>
        @include('reports.pdf.components.chart-dual', [
            'logs' => $logs,
            'leftAxis' => [
                'dataKey' => 'temperature',
                'label' => 'Temperature (°C)',
                'color' => '#305CDE',
                'min' => $data['min_temp'] ?? null,
                'max' => $data['max_temp'] ?? null,
            ],
            'rightAxis' => [
                'dataKey' => 'humidity',
                'label' => 'Humidity (%RH)',
                'color' => 'green',
                'min' => $data['min_hum'] ?? null,
                'max' => $data['max_hum'] ?? null,
            ],
        ])

    @elseif($dataFormation === 'separate_temperature_humidity')
        <div class="subsection-title">Temperature</div>
        @include('reports.pdf.components.chart-single', [
            'logs' => $logs,
            'dataKey' => 'temperature',
            'label' => 'Temperature (°C)',
            'color' => '#305CDE',
            'minThreshold' => $data['min_temp'] ?? null,
            'maxThreshold' => $data['max_temp'] ?? null,
        ])

        <div class="subsection-title" style="margin-top: 15px;">Humidity</div>
        @include('reports.pdf.components.chart-single', [
            'logs' => $logs,
            'dataKey' => 'humidity',
            'label' => 'Humidity (%RH)',
            'color' => 'green',
            'minThreshold' => $data['min_hum'] ?? null,
            'maxThreshold' => $data['max_hum'] ?? null,
        ])

    @elseif($dataFormation === 'combined_probe_temperature')
        <div class="subsection-title">Temperature & Temp Probe</div>
        @include('reports.pdf.components.chart-dual', [
            'logs' => $logs,
            'leftAxis' => [
                'dataKey' => 'temperature',
                'label' => 'Temperature (°C)',
                'color' => '#305CDE',
                'min' => $data['min_temp'] ?? null,
                'max' => $data['max_temp'] ?? null,
            ],
            'rightAxis' => [
                'dataKey' => 'tempprobe',
                'label' => 'Temp Probe (°C)',
                'color' => '#b51bfc',
                'min' => $data['min_tempprobe'] ?? null,
                'max' => $data['max_tempprobe'] ?? null,
            ],
        ])

    @elseif($dataFormation === 'combined_probe_temperature_humidity')
        <div class="subsection-title">Temperature & Temp Probe</div>
        @include('reports.pdf.components.chart-dual', [
            'logs' => $logs,
            'leftAxis' => [
                'dataKey' => 'temperature',
                'label' => 'Temperature (°C)',
                'color' => '#305CDE',
                'min' => $data['min_temp'] ?? null,
                'max' => $data['max_temp'] ?? null,
            ],
            'rightAxis' => [
                'dataKey' => 'tempprobe',
                'label' => 'Temp Probe (°C)',
                'color' => '#b51bfc',
                'min' => $data['min_tempprobe'] ?? null,
                'max' => $data['max_tempprobe'] ?? null,
            ],
        ])

        <div class="subsection-title" style="margin-top: 15px;">Humidity</div>
        @include('reports.pdf.components.chart-single', [
            'logs' => $logs,
            'dataKey' => 'humidity',
            'label' => 'Humidity (%RH)',
            'color' => 'green',
            'minThreshold' => $data['min_hum'] ?? null,
            'maxThreshold' => $data['max_hum'] ?? null,
        ])
    @endif

    @php
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
