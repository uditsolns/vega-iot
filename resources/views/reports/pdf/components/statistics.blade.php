<div class="section-title">Observed Report Summary</div>

@foreach($data['statistics'] as $stat)
    <div class="subsection-title">{{ $stat['label'] }} ({{ $stat['unit'] }})</div>
    <div class="info-grid">
        <div class="info-row">
            <span class="label">Min:</span>
            <span>{{ $stat['min'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Max:</span>
            <span>{{ $stat['max'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Average:</span>
            <span>{{ $stat['avg'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Std Deviation:</span>
            <span>{{ $stat['stddev'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">First Reading:</span>
            <span>{{ $stat['first_val'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Last Reading:</span>
            <span>{{ $stat['last_val'] ?? 'N/A' }} {{ $stat['unit'] }}</span>
        </div>
        <div class="info-row">
            <span class="label">Total Readings:</span>
            <span>{{ number_format($stat['count']) }}</span>
        </div>
        @if(isset($stat['mkt']))
            <div class="info-row">
                <span class="label">MKT:</span>
                <span>{{ $stat['mkt'] }} {{ $stat['unit'] }}</span>
            </div>
        @endif
    </div>
@endforeach

<div class="divider"></div>
