<div class="section-title">Device Info</div>

<div class="info-grid clearfix">
    <div class="info-left">
        <div class="info-row">
            <span class="label">Make:</span>
            <span class="value">{{ $data['make'] ?? 'N/A' }}</span>
        </div>

        <div class="info-row">
            <span class="label">Model:</span>
            <span class="value">{{ $data['model'] ?? 'N/A' }}</span>
        </div>

        <div class="info-row">
            <span class="label">Serial No:</span>
            <span class="value">{{ $data['serialno'] ?? 'N/A' }}</span>
        </div>

        @if(isset($data['instrumentid']))
            <div class="info-row">
                <span class="label">Instrument ID:</span>
                <span class="value">{{ $data['instrumentid'] }}</span>
            </div>
        @endif
    </div>

    <div class="info-right">
        <div class="info-row">
            <span class="label">Device Name:</span>
            <span class="value">{{ $data['device_name'] ?? $data['device_code'] ?? 'N/A' }}</span>
        </div>

        <div class="info-row">
            <span class="label">Temperature Resolution:</span>
            <span class="value">{{ $data['temp_res'] ?? '0.1' }} °C</span>
        </div>

        <div class="info-row">
            <span class="label">Temperature Accuracy:</span>
            <span class="value">± {{ $data['temp_acc'] ?? '0.5' }} °C</span>
        </div>

        @if(isset($data['hum_res']))
            <div class="info-row">
                <span class="label">Humidity Resolution:</span>
                <span class="value">{{ $data['hum_res'] }} %RH</span>
            </div>
        @endif

        @if(isset($data['hum_acc']))
            <div class="info-row">
                <span class="label">Humidity Accuracy:</span>
                <span class="value">± {{ $data['hum_acc'] }} %RH</span>
            </div>
        @endif

        @if(isset($data['tempprobe_res']))
            <div class="info-row">
                <span class="label">Temp Probe Resolution:</span>
                <span class="value">{{ $data['tempprobe_res'] }} °C</span>
            </div>
        @endif

        @if(isset($data['tempprobe_acc']))
            <div class="info-row">
                <span class="label">Temp Probe Accuracy:</span>
                <span class="value">± {{ $data['tempprobe_acc'] }} °C</span>
            </div>
        @endif
    </div>
</div>

<div class="divider"></div>
