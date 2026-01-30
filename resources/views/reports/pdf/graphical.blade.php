@extends('reports.pdf.layouts.base')

@section('content')

    @include('reports.pdf.layouts.header')

    {{-- Device Info Section --}}
    @include('reports.pdf.components.device-info')

    {{-- Logger Summary Section --}}
    @include('reports.pdf.components.logger-summary')

    {{-- Observed Statistics Section --}}
    @include('reports.pdf.components.statistics')

    {{-- Chart Section --}}
    @php
        // Determine which charts to show based on data formation
        $dataFormation = $data['data_formation'] ?? 'single_temperature';
        $logs = $data['logs'];
    @endphp

    @if($dataFormation === 'single_temperature')
        {{-- Single Temperature Chart --}}
        <div class="subsection-title">Temperature Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart', [
                'type' => 'temperature',
                'logs' => $logs,
                'minTemp' => $data['min_temp'] ?? null,
                'maxTemp' => $data['max_temp'] ?? null,
            ])
        </div>

    @elseif($dataFormation === 'combined_temperature_humidity')
        {{-- Combined Temperature & Humidity Chart (Dual Y-Axis) --}}
        <div class="subsection-title">Temperature & Humidity Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart-dual-axis', [
                'logs' => $logs,
                'leftAxis' => [
                    'type' => 'temperature',
                    'label' => 'Temperature (°C)',
                    'color' => '#305CDE',
                    'min' => $data['min_temp'] ?? null,
                    'max' => $data['max_temp'] ?? null,
                ],
                'rightAxis' => [
                    'type' => 'humidity',
                    'label' => 'Humidity (%RH)',
                    'color' => 'green',
                    'min' => $data['min_hum'] ?? null,
                    'max' => $data['max_hum'] ?? null,
                ],
            ])
        </div>

    @elseif($dataFormation === 'separate_temperature_humidity')
        {{-- Separate Temperature Chart --}}
        <div class="subsection-title">Temperature Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart', [
                'type' => 'temperature',
                'logs' => $logs,
                'minTemp' => $data['min_temp'] ?? null,
                'maxTemp' => $data['max_temp'] ?? null,
            ])
        </div>

        {{-- Separate Humidity Chart --}}
        <div class="subsection-title" style="margin-top: 15px;">Humidity Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart', [
                'type' => 'humidity',
                'logs' => $logs,
                'minHum' => $data['min_hum'] ?? null,
                'maxHum' => $data['max_hum'] ?? null,
            ])
        </div>

    @elseif($dataFormation === 'combined_probe_temperature')
        {{-- Combined Temperature & Probe Chart (Dual Y-Axis) --}}
        <div class="subsection-title">Temperature & Temp Probe Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart-dual-axis', [
                'logs' => $logs,
                'leftAxis' => [
                    'type' => 'temperature',
                    'label' => 'Temperature (°C)',
                    'color' => '#305CDE',
                    'min' => $data['min_temp'] ?? null,
                    'max' => $data['max_temp'] ?? null,
                ],
                'rightAxis' => [
                    'type' => 'tempprobe',
                    'label' => 'Temp Probe (°C)',
                    'color' => '#b51bfc',
                    'min' => $data['min_tempprobe'] ?? null,
                    'max' => $data['max_tempprobe'] ?? null,
                ],
            ])
        </div>

    @elseif($dataFormation === 'combined_probe_temperature_humidity')
        {{-- Combined Temperature & Probe Chart --}}
        <div class="subsection-title">Temperature & Temp Probe Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart-dual-axis', [
                'logs' => $logs,
                'leftAxis' => [
                    'type' => 'temperature',
                    'label' => 'Temperature (°C)',
                    'color' => '#305CDE',
                    'min' => $data['min_temp'] ?? null,
                    'max' => $data['max_temp'] ?? null,
                ],
                'rightAxis' => [
                    'type' => 'tempprobe',
                    'label' => 'Temp Probe (°C)',
                    'color' => '#b51bfc',
                    'min' => $data['min_tempprobe'] ?? null,
                    'max' => $data['max_tempprobe'] ?? null,
                ],
            ])
        </div>

        {{-- Separate Humidity Chart --}}
        <div class="subsection-title" style="margin-top: 15px;">Humidity Trend</div>
        <div class="chart-container">
            @include('reports.pdf.components.chart', [
                'type' => 'humidity',
                'logs' => $logs,
                'minHum' => $data['min_hum'] ?? null,
                'maxHum' => $data['max_hum'] ?? null,
            ])
        </div>
    @endif

    @include('reports.pdf.layouts.footer')

@endsection
