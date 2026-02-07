@extends('audit-reports.layouts.base')

@section('content')
    <div class="resource-info">
        @if($resource['type'] === 'user')
            @include('audit-reports.user-info', ['resource' => $resource])
        @else
            @include('audit-reports.device-info', ['resource' => $resource])
        @endif
    </div>

    @if($resource['type'] === 'user')
        <table class="audit-table">
            <thead>
            <tr>
                <th style="width: 5%;">Sr No</th>
                <th style="width: 12%;">Date & Time</th>
                <th style="width: 10%;">Module</th>
                <th style="width: 10%;">Action</th>
                <th style="width: 28%;">Description</th>
                <th style="width: 35%;">Properties</th>
            </tr>
            </thead>
            <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ $activity['sr_no'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($activity['date_time'])->format('d-m-Y H:i:s') }}</td>
                    <td>{{ $activity['module'] }}</td>
                    <td>{{ $activity['action'] }}</td>
                    <td>{{ $activity['description'] }}</td>
                    <td>{{ $activity['properties'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No activities found for this period</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @else
        <table class="audit-table">
            <thead>
            <tr>
                <th style="width: 5%;">Sr No</th>
                <th style="width: 10%;">Date & Time</th>
                <th style="width: 12%;">User</th>
                <th style="width: 10%;">Action</th>
                <th style="width: 28%;">Description</th>
                <th style="width: 35%;">Properties</th>
            </tr>
            </thead>
            <tbody>
            @forelse($activities as $activity)
                <tr>
                    <td>{{ $activity['sr_no'] }}</td>
                    <td>{{ \Carbon\Carbon::parse($activity['date_time'])->format('d-m-Y H:i:s') }}</td>
                    <td>{{ $activity['user'] }}</td>
                    <td>{{ $activity['action'] }}</td>
                    <td>{{ $activity['description'] }}</td>
                    <td>{{ $activity['properties'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 20px;">No activities found for this period</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    @endif
@endsection
