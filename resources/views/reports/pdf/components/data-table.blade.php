@php
    /**
     * Returns CSS class based on value vs thresholds.
     * Critical > Warning > Normal.
     */
    function getSensorColorClass(mixed $value, array $sensor): string {
        if ($value === null) return 'val-normal';

        $v = (float) $value;

        // Critical check
        if (($sensor['min_critical'] !== null && $v < (float)$sensor['min_critical'])
            || ($sensor['max_critical'] !== null && $v > (float)$sensor['max_critical'])) {
            return 'val-critical';
        }

        // Warning check
        if (($sensor['min_warning'] !== null && $v < (float)$sensor['min_warning'])
            || ($sensor['max_warning'] !== null && $v > (float)$sensor['max_warning'])) {
            return 'val-warning';
        }

        return 'val-normal';
    }

    $colCount   = count($sensors);
    $valueWidth = $colCount > 0 ? round((67 / $colCount), 1) : 67;
@endphp

<div class="page-break"></div>
<div class="subsection-title">Tabular Data</div>

<table class="data-table">
    <thead>
    <tr>
        <th style="width:6%">Sr.</th>
        <th style="width:{{ max(20, 27 - ($colCount * 2)) }}%">Date &amp; Time</th>
        @foreach($sensors as $sensor)
            <th style="width:{{ $valueWidth }}%">
                {{ $sensor['label'] }}<br>({{ $sensor['unit'] }})
            </th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @foreach($logs as $i => $log)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $log['timestamp'] }}</td>
            @foreach($sensors as $sensor)
                @php
                    $val   = $log[$sensor['key']] ?? null;
                    $class = getSensorColorClass($val, $sensor);
                @endphp
                <td class="{{ $class }}">
                    {{ $val !== null ? number_format((float)$val, 2) : '–' }}
                </td>
            @endforeach
        </tr>
    @endforeach
    </tbody>
</table>

<div style="margin-top:8px; font-size:7pt">
    <span class="label">Total Intervals:</span> {{ count($logs) }}
    &nbsp;|&nbsp;
    <span class="label">Interval:</span> {{ $data['logger']['interval'] }} mins
    &nbsp;|&nbsp;
    <span style="color:#ff0000; font-weight:bold">■</span> Critical
    &nbsp;
    <span style="color:#ff8c00; font-weight:bold">■</span> Warning
</div>
