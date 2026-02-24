<div class="section-title">Device Information</div>

<div class="info-grid">
    <div class="info-row">
        <span class="label">Device Name:</span>
        <span>{{ $data['device']['device_name'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Device Code:</span>
        <span>{{ $data['device']['device_code'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Serial No:</span>
        <span>{{ $data['device']['device_uid'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Instrument ID:</span>
        <span>{{ $data['device']['id'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Make / Vendor:</span>
        <span>{{ $data['device']['make'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Model:</span>
        <span>{{ $data['device']['model'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Firmware:</span>
        <span>{{ $data['device']['firmware'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Location:</span>
        <span>{{ $data['device']['location'] }}</span>
    </div>
</div>

{{-- Per-sensor accuracy / resolution rows --}}
@foreach($data['sensors'] as $sensor)
    @if($sensor['accuracy'] || $sensor['resolution'])
        <div class="info-grid" style="margin-top:4px">
            <div class="info-row">
                <span class="label">{{ $sensor['label'] }} Resolution:</span>
                <span>{{ $sensor['resolution'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">{{ $sensor['label'] }} Accuracy:</span>
                <span>± {{ $sensor['accuracy'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
        </div>
    @endif
@endforeach

<div class="divider"></div>
