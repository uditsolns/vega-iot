@extends('emails.alerts.layout')

@section('title', 'Alert Triggered')

@section('header-content')
    <span class="alert-badge {{ strtolower($alert->severity->value) }}">
        {{ strtoupper($alert->severity->value) }} ALERT
    </span>
@endsection

@section('content')
    <p>Dear {{ $user->first_name }},</p>

    <p>An alert has been triggered for one of your monitored devices:</p>

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
            <td>Current Value:</td>
            <td><strong>{{ $data['value'] }}</strong></td>
        </tr>
        <tr>
            <td>Threshold:</td>
            <td>{{ $data['threshold'] }}</td>
        </tr>
        <tr>
            <td>Alert Time:</td>
            <td>{{ $alert->started_at->format('Y-m-d H:i:s') }}</td>
        </tr>
    </table>

    <p><strong>Reason:</strong> {{ $alert->reason }}</p>

    <p style="color: #d9534f; font-weight: bold;">
        @if($alert->severity->value === 'critical')
            ⚠️ This is a CRITICAL alert. Immediate action is required.
        @else
            ⚠️ This alert requires your attention.
        @endif
    </p>

    <p>Please take appropriate action to address this alert.</p>
@endsection
