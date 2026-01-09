@extends('emails.alerts.layout')

@section('title', 'Alert Resolved')

@section('header-content')
    <span class="alert-badge info">
        ALERT RESOLVED
    </span>
@endsection

@section('content')
    <p>Dear {{ $user->first_name }},</p>

    <p>An alert has been manually resolved by {{ $alert->resolvedBy->first_name }} {{ $alert->resolvedBy->last_name }}:</p>

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
            <td>Started At:</td>
            <td>{{ $alert->started_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        <tr>
            <td>Resolved At:</td>
            <td>{{ $alert->resolved_at->format('Y-m-d H:i:s') }}</td>
        </tr>
        <tr>
            <td>Duration:</td>
            <td>{{ $alert->duration_formatted ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Resolved By:</td>
            <td>{{ $alert->resolvedBy->first_name }} {{ $alert->resolvedBy->last_name }}</td>
        </tr>
    </table>

    @if($alert->resolve_comment)
        <p><strong>Resolution Comment:</strong> {{ $alert->resolve_comment }}</p>
    @endif

    <p>This alert is now closed.</p>
@endsection
