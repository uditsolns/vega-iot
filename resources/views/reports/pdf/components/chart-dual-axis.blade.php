@php
    use App\Services\Report\PDF\ChartHelper;

    // Prepare chart dimensions
    $chartSetup = ChartHelper::prepareChartData($logs, 'dual', ['padding' => ['top' => 40, 'right' => 80, 'bottom' => 60, 'left' => 60]]);
    extract($chartSetup);

    // Prepare left axis dataset
    $leftDataset = ChartHelper::prepareDataset($logs, $leftAxis['type'], [
        'min' => $leftAxis['min'] ?? null,
        'max' => $leftAxis['max'] ?? null,
    ]);
    $leftAxisRange = ChartHelper::calculateAxisRange($leftDataset['filtered']);

    // Prepare right axis dataset
    $rightDataset = ChartHelper::prepareDataset($logs, $rightAxis['type'], [
        'min' => $rightAxis['min'] ?? null,
        'max' => $rightAxis['max'] ?? null,
    ]);
    $rightAxisRange = ChartHelper::calculateAxisRange($rightDataset['filtered']);
@endphp

<svg width="{{ $chartWidth }}" height="{{ $chartHeight }}" xmlns="http://www.w3.org/2000/svg" style="background-color: #ffffff;">
    {{-- Background --}}
    <rect width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="#ffffff"/>

    {{-- Grid and axes --}}
    @for($i = 0; $i <= 10; $i++)
        @php
            $y = $padding['top'] + ($plotHeight / 10) * $i;
            $leftValue = $leftAxisRange['max'] - ($leftAxisRange['range'] / 10) * $i;
            $rightValue = $rightAxisRange['max'] - ($rightAxisRange['range'] / 10) * $i;
        @endphp
        <line x1="{{ $padding['left'] }}" y1="{{ $y }}"
              x2="{{ $padding['left'] + $plotWidth }}" y2="{{ $y }}"
              stroke="#e0e0e0" stroke-width="0.5"/>
        <text x="{{ $padding['left'] - 5 }}" y="{{ $y + 3 }}"
              text-anchor="end" font-size="10" font-weight="bold" fill="{{ $leftAxis['color'] }}">
            {{ number_format($leftValue, 0) }}
        </text>
        <text x="{{ $padding['left'] + $plotWidth + 5 }}" y="{{ $y + 3 }}"
              text-anchor="start" font-size="10" font-weight="bold" fill="{{ $rightAxis['color'] }}">
            {{ number_format($rightValue, 0) }}
        </text>
    @endfor

    {{-- X-axis labels --}}
    @php $labelInterval = max(1, floor($dataPoints / 10)); @endphp
    @foreach($timestamps as $index => $timestamp)
        @if($index % $labelInterval === 0 || $index === $dataPoints - 1)
            @php $x = ChartHelper::getXPosition($index, $dataPoints, $plotWidth, $padding['left']); @endphp
            <text x="{{ $x }}" y="{{ $padding['top'] + $plotHeight + 15 }}"
                  text-anchor="middle" font-size="8" font-weight="bold" fill="#000000"
                  transform="rotate(-45, {{ $x }}, {{ $padding['top'] + $plotHeight + 15 }})">
                {{ Str::limit($timestamp, 15, '') }}
            </text>
        @endif
    @endforeach

    {{-- Axis labels --}}
    <text x="{{ $chartWidth / 2 }}" y="{{ $chartHeight - 5 }}"
          text-anchor="middle" font-size="12" font-weight="bold" fill="#000000">Date &amp; Time</text>
    <text x="15" y="{{ $chartHeight / 2 }}"
          text-anchor="middle" font-size="12" font-weight="bold" fill="{{ $leftAxis['color'] }}"
          transform="rotate(-90, 15, {{ $chartHeight / 2 }})">{{ $leftAxis['label'] }}</text>
    <text x="{{ $chartWidth - 15 }}" y="{{ $chartHeight / 2 }}"
          text-anchor="middle" font-size="12" font-weight="bold" fill="{{ $rightAxis['color'] }}"
          transform="rotate(90, {{ $chartWidth - 15 }}, {{ $chartHeight / 2 }})">{{ $rightAxis['label'] }}</text>

    {{-- Plot left axis data --}}
    @php
        $leftPoints = [];
        foreach ($leftDataset['values'] as $i => $value) {
            if ($value !== null) {
                $leftPoints[] = [
                    'x' => ChartHelper::getXPosition($i, $dataPoints, $plotWidth, $padding['left']),
                    'y' => ChartHelper::getYPosition($value, $leftAxisRange['min'], $leftAxisRange['max'], $plotHeight, $padding['top']),
                ];
            }
        }
    @endphp
    @foreach($leftPoints as $i => $point)
        @if($i > 0)
            <line x1="{{ $leftPoints[$i-1]['x'] }}" y1="{{ $leftPoints[$i-1]['y'] }}"
                  x2="{{ $point['x'] }}" y2="{{ $point['y'] }}"
                  stroke="{{ $leftAxis['color'] }}" stroke-width="2"/>
        @endif
    @endforeach

    {{-- Plot right axis data --}}
    @php
        $rightPoints = [];
        foreach ($rightDataset['values'] as $i => $value) {
            if ($value !== null) {
                $rightPoints[] = [
                    'x' => ChartHelper::getXPosition($i, $dataPoints, $plotWidth, $padding['left']),
                    'y' => ChartHelper::getYPosition($value, $rightAxisRange['min'], $rightAxisRange['max'], $plotHeight, $padding['top']),
                ];
            }
        }
    @endphp
    @foreach($rightPoints as $i => $point)
        @if($i > 0)
            <line x1="{{ $rightPoints[$i-1]['x'] }}" y1="{{ $rightPoints[$i-1]['y'] }}"
                  x2="{{ $point['x'] }}" y2="{{ $point['y'] }}"
                  stroke="{{ $rightAxis['color'] }}" stroke-width="2"/>
        @endif
    @endforeach

    {{-- Threshold lines for both axes --}}
    @if($leftDataset['minThreshold'])
        @php $y = ChartHelper::getYPosition($leftDataset['minThreshold'], $leftAxisRange['min'], $leftAxisRange['max'], $plotHeight, $padding['top']); @endphp
        <line x1="{{ $padding['left'] }}" y1="{{ $y }}" x2="{{ $padding['left'] + $plotWidth }}" y2="{{ $y }}"
              stroke="{{ $leftAxis['color'] }}" stroke-width="1" stroke-dasharray="10,10"/>
        <text x="{{ $padding['left'] + 5 }}" y="{{ $y + 12 }}" font-size="10" font-weight="bold" fill="{{ $leftAxis['color'] }}">
            Min: {{ number_format($leftDataset['minThreshold'], 1) }}
        </text>
    @endif

    {{-- Add similar for max thresholds and right axis thresholds --}}

    {{-- Legend & Border --}}
    <rect x="{{ $padding['left'] }}" y="{{ $padding['top'] }}"
          width="{{ $plotWidth }}" height="{{ $plotHeight }}"
          fill="none" stroke="#000000" stroke-width="1"/>
</svg>
