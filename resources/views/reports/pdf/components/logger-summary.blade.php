<div class="section-title">Logger Summary</div>

<div class="info-grid">
    <div class="info-row">
        <span class="label">Start Date &amp; Time:</span>
        <span>{{ $data['logger']['start_dt'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">End Date &amp; Time:</span>
        <span>{{ $data['logger']['end_dt'] }}</span>
    </div>
    <div class="info-row">
        <span class="label">Recording Interval:</span>
        <span>{{ $data['logger']['recording_interval'] }} mins</span>
    </div>
    <div class="info-row">
        <span class="label">Sending Interval:</span>
        <span>{{ $data['logger']['sending_interval'] }} mins</span>
    </div>
    <div class="info-row">
        <span class="label">Report Interval:</span>
        <span>{{ $data['logger']['interval'] }} mins</span>
    </div>
</div>

{{-- Per-sensor threshold rows --}}
@foreach($data['sensors'] as $sensor)
    @if($sensor['supports_threshold'])
        <div class="info-grid" style="margin-top:6px">
            <div class="info-row" style="grid-column:1/-1">
                <span class="label">{{ $sensor['label'] }} Thresholds</span>
            </div>
            <div class="info-row">
                <span class="label">Min Critical:</span>
                <span>{{ $sensor['min_critical'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Max Critical:</span>
                <span>{{ $sensor['max_critical'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Min Warning:</span>
                <span>{{ $sensor['min_warning'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
            <div class="info-row">
                <span class="label">Max Warning:</span>
                <span>{{ $sensor['max_warning'] ?? '–' }} {{ $sensor['unit'] }}</span>
            </div>
        </div>
    @endif
@endforeach

<div class="divider"></div>
