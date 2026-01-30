@php
    use App\Services\Report\PDF\ChartHelper;

    // Prepare chart dimensions and data
    $chartSetup = ChartHelper::prepareChartData($logs, $type ?? 'temperature');
    extract($chartSetup); // $chartWidth, $chartHeight, $padding, $plotWidth, $plotHeight, $timestamps, $dataPoints

    // Prepare datasets
    $datasets = [];
    if (isset($type) && $type === 'temperature') {
        $dataset = ChartHelper::prepareDataset($logs, 'temperature', [
            'min' => $minTemp ?? null,
            'max' => $maxTemp ?? null,
        ]);
        $datasets[] = array_merge($dataset, [
            'label' => 'Temperature (°C)',
            'color' => '#305CDE',
        ]);
    } elseif (isset($type) && $type === 'humidity') {
        $dataset = ChartHelper::prepareDataset($logs, 'humidity', [
            'min' => $minHum ?? null,
            'max' => $maxHum ?? null,
        ]);
        $datasets[] = array_merge($dataset, [
            'label' => 'Humidity (%RH)',
            'color' => 'green',
        ]);
    } elseif (isset($type) && $type === 'tempprobe') {
        $dataset = ChartHelper::prepareDataset($logs, 'tempprobe', [
            'min' => $minTempProbe ?? null,
            'max' => $maxTempProbe ?? null,
        ]);
        $datasets[] = array_merge($dataset, [
            'label' => 'Temp Probe (°C)',
            'color' => '#b51bfc',
        ]);
    }

    // Calculate axis range
    $allValues = array_merge(...array_column($datasets, 'filtered'));
    $axisRange = ChartHelper::calculateAxisRange($allValues);
    extract($axisRange); // $min, $max, $range
@endphp

<svg width="{{ $chartWidth }}" height="{{ $chartHeight }}" xmlns="http://www.w3.org/2000/svg" style="background-color: #ffffff;">
    {{-- Background --}}
    <rect width="{{ $chartWidth }}" height="{{ $chartHeight }}" fill="#ffffff"/>

    {{-- Grid lines --}}
    @for($i = 0; $i <= 10; $i++)
        @php
            $y = $padding['top'] + ($plotHeight / 10) * $i;
            $gridValue = $max - ($range / 10) * $i;
        @endphp
        <line x1="{{ $padding['left'] }}" y1="{{ $y }}"
              x2="{{ $padding['left'] + $plotWidth }}" y2="{{ $y }}"
              stroke="#e0e0e0" stroke-width="0.5"/>
        <text x="{{ $padding['left'] - 5 }}" y="{{ $y + 3 }}"
              text-anchor="end" font-size="10" font-weight="bold" fill="#000000">
            {{ number_format($gridValue, 0) }}
        </text>
    @endfor

    {{-- X-axis labels --}}
    @php $labelInterval = max(1, floor($dataPoints / 10)); @endphp
    @foreach($timestamps as $index => $timestamp)
        @if($index % $labelInterval === 0 || $index === $dataPoints - 1)
            @php
                $x = ChartHelper::getXPosition($index, $dataPoints, $plotWidth, $padding['left']);
            @endphp
            <text x="{{ $x }}" y="{{ $padding['top'] + $plotHeight + 15 }}"
                  text-anchor="middle" font-size="8" font-weight="bold" fill="#000000"
                  transform="rotate(-45, {{ $x }}, {{ $padding['top'] + $plotHeight + 15 }})">
                {{ Str::limit($timestamp, 15, '') }}
            </text>
        @endif
    @endforeach

    {{-- Axis labels --}}
    <text x="{{ $chartWidth / 2 }}" y="{{ $chartHeight - 5 }}"
          text-anchor="middle" font-size="12" font-weight="bold" fill="#000000">
        Date &amp; Time
    </text>
    <text x="15" y="{{ $chartHeight / 2 }}"
          text-anchor="middle" font-size="12" font-weight="bold" fill="#000000"
          transform="rotate(-90, 15, {{ $chartHeight / 2 }})">
        {{ $datasets[0]['label'] ?? 'Value' }}
    </text>

    {{-- Plot data --}}
    @foreach($datasets as $dataset)
        @php
            $points = [];
            foreach ($dataset['values'] as $i => $value) {
                if ($value !== null) {
                    $x = ChartHelper::getXPosition($i, $dataPoints, $plotWidth, $padding['left']);
                    $y = ChartHelper::getYPosition($value, $min, $max, $plotHeight, $padding['top']);
                    $points[] = compact('x', 'y', 'value');
                }
            }
        @endphp

        @foreach($points as $i => $point)
            @if($i > 0)
                <line x1="{{ $points[$i-1]['x'] }}" y1="{{ $points[$i-1]['y'] }}"
                      x2="{{ $point['x'] }}" y2="{{ $point['y'] }}"
                      stroke="{{ $dataset['color'] }}" stroke-width="2" fill="none"/>
            @endif
        @endforeach
    @endforeach

    {{-- Threshold lines --}}
    @foreach($datasets as $dataset)
        @if($dataset['minThreshold'] !== null)
            @php $y = ChartHelper::getYPosition($dataset['minThreshold'], $min, $max, $plotHeight, $padding['top']); @endphp
            <line x1="{{ $padding['left'] }}" y1="{{ $y }}"
                  x2="{{ $padding['left'] + $plotWidth }}" y2="{{ $y }}"
                  stroke="{{ $dataset['color'] }}" stroke-width="1" stroke-dasharray="10,10"/>
            <text x="{{ $padding['left'] + 5 }}" y="{{ $y + 12 }}"
                  font-size="10" font-weight="bold" fill="{{ $dataset['color'] }}">
                Min: {{ number_format($dataset['minThreshold'], 1) }}
            </text>
        @endif
        @if($dataset['maxThreshold'] !== null)
            @php $y = ChartHelper::getYPosition($dataset['maxThreshold'], $min, $max, $plotHeight, $padding['top']); @endphp
            <line x1="{{ $padding['left'] }}" y1="{{ $y }}"
                  x2="{{ $padding['left'] + $plotWidth }}" y2="{{ $y }}"
                  stroke="{{ $dataset['color'] }}" stroke-width="1" stroke-dasharray="10,10"/>
            <text x="{{ $padding['left'] + 5 }}" y="{{ $y - 5 }}"
                  font-size="10" font-weight="bold" fill="{{ $dataset['color'] }}">
                Max: {{ number_format($dataset['maxThreshold'], 1) }}
            </text>
        @endif
    @endforeach

    {{-- Legend --}}
    @foreach($datasets as $i => $dataset)
        <rect x="{{ $padding['left'] + ($i * 150) }}" y="{{ $chartHeight - 20 }}"
              width="15" height="3" fill="{{ $dataset['color'] }}"/>
        <text x="{{ $padding['left'] + ($i * 150) + 20 }}" y="{{ $chartHeight - 17 }}"
              font-size="9" fill="#000000">{{ $dataset['label'] }}</text>
    @endforeach

    {{-- Border --}}
    <rect x="{{ $padding['left'] }}" y="{{ $padding['top'] }}"
          width="{{ $plotWidth }}" height="{{ $plotHeight }}"
          fill="none" stroke="#000000" stroke-width="1"/>
</svg>
