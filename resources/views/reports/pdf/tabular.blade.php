@extends('reports.pdf.layouts.base')

@section('content')
    @include('reports.pdf.components.device-info')
    @include('reports.pdf.components.logger-summary')
    @include('reports.pdf.components.statistics')

    @include('reports.pdf.components.data-table', [
        'logs'    => $data['logs'],
        'sensors' => $data['sensors'],
        'data'    => $data,
    ])
@endsection
