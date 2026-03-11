@extends('alerts.layouts.base')

@section('content')
    <div class="section-title">Details</div>

    <table class="details-table">
        <tr>
            <td class="label">Device ID</td>
            <td class="value">{{ $alert->device->device_code }}</td>
        </tr>
        <tr>
            <td class="label">Device Name</td>
            <td class="value">{{ $alert->device->device_name ?? $alert->device->device_code }}</td>
        </tr>
        <tr>
            <td class="label">Location</td>
            <td class="value">{{ $locationPath }}</td>
        </tr>
        <tr>
            <td class="label">Sensor</td>
            <td class="value">{{ $alert->sensor_label }}</td>
        </tr>
        <tr>
            <td class="label">Excursion Type</td>
            <td class="value">{{ $alert->sensor_label }} - {{ ucfirst($alert->severity->value) }}</td>
        </tr>
        <tr>
            <td class="label">Observed Value</td>
            <td class="value">{{ $alert->trigger_value }} {{ $alert->sensor_unit }}</td>
        </tr>
        <tr>
            <td class="label">Acceptable Operating Range</td>
            <td class="value">{{ $operatingRange }}</td>
        </tr>
        <tr>
            <td class="label">Triggered At</td>
            <td class="value">{{ $alert->started_at->format('d/m/Y, H:i:s') }}</td>
        </tr>
        <tr>
            <td class="label">Closed At</td>
            <td class="value">{{ $alert->ended_at ? $alert->ended_at->format('d/m/Y, H:i:s') : '—' }}</td>
        </tr>

        {{-- Shown when the alert was acknowledged (any status that went through acknowledgement) --}}
        @if($showAcknowledgedBy)
            <tr>
                <td class="label">Acknowledged By</td>
                <td class="value">{{ $alert->acknowledgedBy?->email ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Acknowledged At</td>
                <td class="value">{{ $alert->acknowledged_at->format('d/m/Y, H:i:s') }}</td>
            </tr>
            <tr>
                <td class="label">Possible Cause</td>
                <td class="value">{{ $alert->possible_cause ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Root Cause</td>
                <td class="value">{{ $alert->root_cause ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Corrective Action</td>
                <td class="value">{{ $alert->corrective_action ?? '—' }}</td>
            </tr>
        @endif

        {{-- Only shown when a user manually resolved the alert --}}
        @if($showResolvedBy)
            <tr>
                <td class="label">Resolved By</td>
                <td class="value">{{ $alert->resolvedBy?->email ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Resolved At</td>
                <td class="value">{{ $alert->resolved_at->format('d/m/Y, H:i:s') }}</td>
            </tr>
        @endif

        <tr>
            <td class="label">Status</td>
            <td class="value">{{ $alert->status->label() }}</td>
        </tr>
    </table>
@endsection
