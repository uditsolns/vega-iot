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

<div class="section-title">Device Info</div>

<div class="info-grid">
    <div class="info-row">
        <span class="label">Make:</span>
        <span class="value">{{ $data['make'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Device Name:</span>
        <span class="value">{{ $data['device_name'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Model:</span>
        <span class="value">{{ $data['model'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Serial No:</span>
        <span class="value">{{ $data['serialno'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Instrument ID:</span>
        <span class="value">{{ $data['instrumentid'] ?? 'N/A' }}</span>
    </div>

    <div class="info-row">
        <span class="label">Temperature Resolution:</span>
        <span class="value">{{ $data['temp_res'] ?? '0.1' }} °C</span>
    </div>

    <div class="info-row">
        <span class="label">Temperature Accuracy:</span>
        <span class="value">± {{ $data['temp_acc'] ?? '0.5' }} °C</span>
    </div>

    @if($showProbe)
        <div class="info-row">
            <span class="label">Temp Probe Resolution:</span>
            <span class="value">{{ $data['tempprobe_res'] ?? '0.1' }} °C</span>
        </div>

        <div class="info-row">
            <span class="label">Temp Probe Accuracy:</span>
            <span class="value">± {{ $data['tempprobe_acc'] ?? '0.5' }} °C</span>
        </div>
    @endif

    @if($showHumidity)
        <div class="info-row">
            <span class="label">Humidity Resolution:</span>
            <span class="value">{{ $data['hum_res'] ?? '1.0' }} %RH</span>
        </div>

        <div class="info-row">
            <span class="label">Humidity Accuracy:</span>
            <span class="value">± {{ $data['hum_acc'] ?? '3.0' }} %RH</span>
        </div>
    @endif
</div>

<div class="divider"></div>
