@extends('reports.pdf.layouts.base')

@section('content')
    @include('reports.pdf.components.device-info')
    @include('reports.pdf.components.logger-summary')
    @include('reports.pdf.components.statistics')

    <div class="section-title">Trend Charts</div>

    @php
        $logs    = $data['logs'];
        $sensors = $data['sensors'];
        $palette = ['#305CDE', '#16a34a', '#b51bfc', '#e67e00', '#e11d48', '#0891b2', '#7c3aed', '#d97706'];
    @endphp

    @foreach($sensors as $index => $sensor)
        <div class="subsection-title">{{ $sensor['label'] }}</div>
        @include('reports.pdf.components.chart-sensor', [
            'sensor'     => $sensor,
            'logs'       => $logs,
            'chartColor' => $palette[$index % count($palette)],
        ])
    @endforeach

    @include('reports.pdf.components.data-table', [
        'logs'    => $logs,
        'sensors' => $sensors,
        'data'    => $data,
    ])
@endsection
