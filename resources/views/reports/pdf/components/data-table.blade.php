@php
    // Helper function to determine color class based on thresholds
    function getColorClass($value, $min, $max, $minWarn = null, $maxWarn = null) {
        if ($value === null) return 'normal';

        $low = min($min ?? PHP_FLOAT_MAX, $max ?? PHP_FLOAT_MAX);
        $high = max($min ?? PHP_FLOAT_MIN, $max ?? PHP_FLOAT_MIN);

        // Critical if outside min/max
        if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            return 'critical';
        }

        // Warning if outside warning thresholds (if provided)
        if ($minWarn !== null && $maxWarn !== null) {
            $lowWarn = min($minWarn, $maxWarn);
            $highWarn = max($minWarn, $maxWarn);
            if ($value < $lowWarn || $value > $highWarn) {
                return 'warning';
            }
        }

        return 'normal';
    }
@endphp

<div class="subsection-title">Tabular Data</div>

<table class="data-table">
    <thead>
    <tr>
        <th style="width: 5%;">Sr. No.</th>
        <th style="width: 18%;">Date</th>
        <th style="width: 10%;">Time</th>

        @if(in_array('temperature', $columns))
            <th style="width: {{ in_array('tempprobe', $columns) || in_array('humidity', $columns) ? '11%' : '22%' }};">
                Temperature<br/>(°C)
            </th>
        @endif

        @if(in_array('tempprobe', $columns))
            <th style="width: {{ in_array('humidity', $columns) ? '11%' : '22%' }};">
                Temp Probe<br/>(°C)
            </th>
        @endif

        @if(in_array('humidity', $columns))
            <th style="width: {{ in_array('temperature', $columns) || in_array('tempprobe', $columns) ? '11%' : '22%' }};">
                Humidity<br/>(%RH)
            </th>
        @endif

        @if(in_array('alarm', $columns))
            <th style="width: 12%;">Alarm Status</th>
        @endif

        <th style="width: 22%;">Remarks</th>
    </tr>
    </thead>
    <tbody>
    @foreach($logs as $index => $log)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ \Carbon\Carbon::parse($log['timestamp'])->format('d-m-Y') }}</td>
            <td>{{ \Carbon\Carbon::parse($log['timestamp'])->format('H:i:s') }}</td>

            @if(in_array('temperature', $columns))
                @php
                    $tempClass = getColorClass(
                        $log['temperature'] ?? null,
                        $data['min_temp'] ?? null,
                        $data['max_temp'] ?? null,
                        $data['min_temp_warn'] ?? null,
                        $data['max_temp_warn'] ?? null
                    );
                @endphp
                <td class="{{ $tempClass }}">
                    {{ $log['temperature'] !== null ? number_format($log['temperature'], 1) : 'N/A' }}
                </td>
            @endif

            @if(in_array('tempprobe', $columns))
                @php
                    $probeClass = getColorClass(
                        $log['tempprobe'] ?? null,
                        $data['min_tempprobe'] ?? null,
                        $data['max_tempprobe'] ?? null,
                        $data['min_tempprobe_warn'] ?? null,
                        $data['max_tempprobe_warn'] ?? null
                    );
                @endphp
                <td class="{{ $probeClass }}">
                    {{ $log['tempprobe'] !== null ? number_format($log['tempprobe'], 1) : 'N/A' }}
                </td>
            @endif

            @if(in_array('humidity', $columns))
                @php
                    $humClass = getColorClass(
                        $log['humidity'] ?? null,
                        $data['min_hum'] ?? null,
                        $data['max_hum'] ?? null,
                        $data['min_hum_warn'] ?? null,
                        $data['max_hum_warn'] ?? null
                    );
                @endphp
                <td class="{{ $humClass }}">
                    {{ $log['humidity'] !== null ? number_format($log['humidity'], 1) : 'N/A' }}
                </td>
            @endif

            @if(in_array('alarm', $columns))
                <td>{{ $log['alarm_status'] ?? 'Normal' }}</td>
            @endif

            <td style="text-align: left; padding-left: 5px;">
                {{ $log['remarks'] ?? '' }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

{{-- Summary at bottom of table --}}
<div style="margin-top: 10px; font-size: 8pt;">
    <span class="label">Total Records:</span> {{ count($logs) }}
    @if(isset($data['out_of_range_count']))
        <span style="margin-left: 15px;" class="label">Out of Range:</span>
        <span class="critical">{{ $data['out_of_range_count'] }}</span>
    @endif
</div>
