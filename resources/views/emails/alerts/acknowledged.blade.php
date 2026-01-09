@extends('emails.alerts.layout')

@section('title', 'Alert Acknowledged')

@section('header-content')
    <span class="alert-badge info">
        ALERT ACKNOWLEDGED
    </span>
@endsection

@section('content')
    <p>Dear {{ $user->first_name }},</p>

    <p>An alert has been acknowledged by {{ $alert->acknowledgedBy->first_name }} {{ $alert->acknowledgedBy->last_name }}:</p>

    <table class="info-table">
        <tr>
            <td>Device Code:</td>
            <td>{{ $device->device_code }}</td>
        </tr>
        <tr>
            <td>Device Name:</td>
            <td>{{ $device->device_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Location:</td>
            <td>{{ $data['location'] }}</td>
        </tr>
        <tr>
            <td>Alert Type:</td>
            <td>{{ ucfirst($alert->type->value) }}</td>
        </tr>
        <tr>
            <td>Acknowledged By:</td>
            <td>{{ $alert->acknowledgedBy->first_name }} {{ $alert->acknowledgedBy->last_name }}</td>
        </tr>
        <tr>
            <td>Acknowledged At:</td>
            <td>{{ $alert->acknowledged_at->format('Y-m-d H:i:s') }}</td>
        </tr>
    </table>

    @if($alert->acknowledge_comment)
        <p><strong>Comment:</strong> {{ $alert->acknowledge_comment }}</p>
    @endif

    <p>The alert is being monitored and will be resolved when conditions return to normal or manually closed.</p>
@endsection
