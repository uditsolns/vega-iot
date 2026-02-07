@php
    $dataFormation = $data['data_formation'] ?? 'single_temperature';

    $showHumidity = in_array($dataFormation, [
        'combined_temperature_humidity',
        'separate_temperature_humidity',
        'combined_probe_temperature_humidity'
    ]);

    $showProbe = in_array($dataFormation, [
        'combined_probe_temperature',
        'combined_probe_temperature_humidity'
    ]);
@endphp

<div class="section-title">Logger Summary</div>

<div class="info-grid">
    <div class="info-row">
        <span class="label">Start Date & Time:</span>
        <span class="value">{{ $data['start_dt'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Sending Interval:</span>
        <span class="value">{{ $data['sending_interval'] ?? '15' }} mins</span>
    </div>

    <div class="info-row">
        <span class="label">End Date & Time:</span>
        <span class="value">{{ $data['end_dt'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Recording Interval:</span>
        <span class="value">{{ $data['record_interval'] ?? '5' }} mins</span>
    </div>

    <div class="info-row">
        <span class="label">Min Set Temperature:</span>
        <span class="value">{{ $data['min_temp'] ?? '20' }} °C</span>
    </div>

    <div class="info-row">
        <span class="label">Max Set Temperature:</span>
        <span class="value">{{ $data['max_temp'] ?? '50' }} °C</span>
    </div>

    @if($showProbe)
        <div class="info-row">
            <span class="label">Min Set Temp Probe:</span>
            <span class="value">{{ $data['min_tempprobe'] ?? '20' }} °C</span>
        </div>

        <div class="info-row">
            <span class="label">Max Set Temp Probe:</span>
            <span class="value">{{ $data['max_tempprobe'] ?? '50' }} °C</span>
        </div>
    @endif

    @if($showHumidity)
        <div class="info-row">
            <span class="label">Min Set Humidity:</span>
            <span class="value">{{ $data['min_hum'] ?? '40' }} %RH</span>
        </div>

        <div class="info-row">
            <span class="label">Max Set Humidity:</span>
            <span class="value">{{ $data['max_hum'] ?? '90' }} %RH</span>
        </div>
    @endif
</div>

<div class="divider"></div>
