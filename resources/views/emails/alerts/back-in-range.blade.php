@extends('emails.alerts.layout')

@section('title', 'Device Back in Range')

@section('header-content')
    <span class="alert-badge info">
        BACK IN RANGE
    </span>
@endsection

@section('content')
    <p>Dear {{ $user->first_name }},</p>

    <p>Good news! The device has returned to normal operating range:</p>

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
            <td>Alert Started:</td>
            <td>{{ $alert->started_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        <tr>
            <td>Auto-Resolved:</td>
            <td>{{ $alert->ended_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        <tr>
            <td>Duration:</td>
            <td>{{ $alert->duration_formatted ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Current Value:</td>
            <td>{{ $data['value'] }}</td>
        </tr>
    </table>

    <p style="color: #28a745; font-weight: bold;">
        ✓ The sensor readings have returned to normal. The alert has been automatically resolved.
    </p>

    <p>Continue monitoring the device to ensure stable operation.</p>
@endsection
