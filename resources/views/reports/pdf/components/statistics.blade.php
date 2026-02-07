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

<div class="section-title">Observed Report Summary</div>

<div class="info-grid">
    @if(isset($data['minTempData']))
        <div class="info-row">
            <span class="label">Min Temperature:</span>
            <span class="value">{{ number_format($data['minTempData'], 1) }} °C</span>
        </div>
    @endif

    @if(isset($data['maxTempData']))
        <div class="info-row">
            <span class="label">Max Temperature:</span>
            <span class="value">{{ number_format($data['maxTempData'], 1) }} °C</span>
        </div>
    @endif

    @if(isset($data['mkt']))
        <div class="info-row">
            <span class="label">MKT:</span>
            <span class="value">{{ number_format($data['mkt'], 2) }} °C</span>
        </div>
    @endif

    @if($showProbe && isset($data['minTempProbeData']))
        <div class="info-row">
            <span class="label">Min Temp Probe:</span>
            <span class="value">{{ number_format($data['minTempProbeData'], 1) }} °C</span>
        </div>
    @endif

    @if($showProbe && isset($data['maxTempProbeData']))
        <div class="info-row">
            <span class="label">Max Temp Probe:</span>
            <span class="value">{{ number_format($data['maxTempProbeData'], 1) }} °C</span>
        </div>
    @endif

    @if($showHumidity && isset($data['minHumData']))
        <div class="info-row">
            <span class="label">Min Humidity:</span>
            <span class="value">{{ number_format($data['minHumData'], 1) }} %RH</span>
        </div>
    @endif

    @if($showHumidity && isset($data['maxHumData']))
        <div class="info-row">
            <span class="label">Max Humidity:</span>
            <span class="value">{{ number_format($data['maxHumData'], 1) }} %RH</span>
        </div>
    @endif

    @if(isset($data['avgTemp']))
        <div class="info-row">
            <span class="label">Avg Temperature:</span>
            <span class="value">{{ number_format($data['avgTemp'], 2) }} °C</span>
        </div>
    @endif

    @if($showProbe && isset($data['avgTempProbe']))
        <div class="info-row">
            <span class="label">Avg Temp Probe:</span>
            <span class="value">{{ number_format($data['avgTempProbe'], 2) }} °C</span>
        </div>
    @endif

    @if($showHumidity && isset($data['avgHum']))
        <div class="info-row">
            <span class="label">Avg Humidity:</span>
            <span class="value">{{ number_format($data['avgHum'], 2) }} %RH</span>
        </div>
    @endif
</div>

<div class="divider"></div>
