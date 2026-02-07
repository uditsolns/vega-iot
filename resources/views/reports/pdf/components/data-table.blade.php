@php
    function getColorClass($value, $min, $max, $minWarn = null, $maxWarn = null) {
        if ($value === null) return 'normal';

        if (($min !== null && $value < $min) || ($max !== null && $value > $max)) {
            return 'critical';
        }

        if ($minWarn !== null && $maxWarn !== null) {
            if ($value < $minWarn || $value > $maxWarn) {
                return 'warning';
            }
        }

        return 'normal';
    }
@endphp

<div class="page-break"></div>

<div class="subsection-title">Tabular Data</div>

<table class="data-table">
    <thead>
    <tr>
        <th style="width: 5%;">Sr. No.</th>
        <th style="width: 18%;">Date</th>
        <th style="width: 10%;">Time</th>

        @if(in_array('temperature', $columns))
            <th style="width: {{ count($columns) > 1 ? '11%' : '22%' }};">Temp (°C)</th>
        @endif

        @if(in_array('tempprobe', $columns))
            <th style="width: {{ count($columns) > 2 ? '11%' : '22%' }};">Probe (°C)</th>
        @endif

        @if(in_array('humidity', $columns))
            <th style="width: {{ count($columns) > 1 ? '11%' : '22%' }};">Humidity (%RH)</th>
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
                        $data['min_warn_temp'] ?? null,
                        $data['max_warn_temp'] ?? null
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
                        $data['min_warn_tempProbe'] ?? null,
                        $data['max_Warn_tempProbe'] ?? null
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
                        $data['min_warn_hum'] ?? null,
                        $data['max_warn_hum'] ?? null
                    );
                @endphp
                <td class="{{ $humClass }}">
                    {{ $log['humidity'] !== null ? number_format($log['humidity'], 1) : 'N/A' }}
                </td>
            @endif

            <td style="text-align: left; padding-left: 5px;"></td>
        </tr>
    @endforeach
    </tbody>
</table>

<div style="margin-top: 10px; font-size: 7pt;">
    <span class="label">Total Records:</span> {{ count($logs) }}
</div>
