<div class="section-title">Logger Summary</div>

<div class="info-grid clearfix">
    <div class="info-left">
        <div class="info-row">
            <span class="label">Start Date & Time:</span>
            <span class="value">{{ $data['start_dt'] ?? 'N/A' }}</span>
        </div>

        <div class="info-row">
            <span class="label">End Date & Time:</span>
            <span class="value">{{ $data['end_dt'] ?? 'N/A' }}</span>
        </div>

        <div class="info-row">
            <span class="label">Recording Interval:</span>
            <span class="value">{{ $data['record_interval'] ?? '5' }} mins</span>
        </div>
    </div>

    <div class="info-right">
        <div class="info-row">
            <span class="label">Sending Interval:</span>
            <span class="value">{{ $data['sending_interval'] ?? '15' }} mins</span>
        </div>

        <div class="info-row">
            <span class="label">Min Set Temperature:</span>
            <span class="value">{{ $data['min_temp'] ?? '20' }} °C</span>
            <span class="label"> / Max Set Temperature:</span>
            <span class="value">{{ $data['max_temp'] ?? '50' }} °C</span>
        </div>

        @if(isset($data['min_hum']) && isset($data['max_hum']))
            <div class="info-row">
                <span class="label">Min Set Humidity:</span>
                <span class="value">{{ $data['min_hum'] }} %RH</span>
                <span class="label"> / Max Set Humidity:</span>
                <span class="value">{{ $data['max_hum'] }} %RH</span>
            </div>
        @endif

        @if(isset($data['min_tempprobe']) && isset($data['max_tempprobe']))
            <div class="info-row">
                <span class="label">Min Set Temp Probe:</span>
                <span class="value">{{ $data['min_tempprobe'] }} °C</span>
                <span class="label"> / Max Set Temp Probe:</span>
                <span class="value">{{ $data['max_tempprobe'] }} °C</span>
            </div>
        @endif
    </div>
</div>

<div class="divider"></div>
