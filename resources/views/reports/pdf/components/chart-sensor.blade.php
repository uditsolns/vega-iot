@php
    $canvasId = 'canvas_' . $sensor['device_sensor_id'] . '_' . uniqid();
    $timestamps = array_column($logs, 'timestamp');
    $values     = array_column($logs, $sensor['key']);
    $color      = $chartColor ?? '#305CDE';

    // Build annotation config for threshold lines
    $annotations = [];
    if ($sensor['supports_threshold']) {
        if ($sensor['min_critical'] !== null) {
            $annotations['minCritical'] = [
                'type' => 'line', 'yMin' => $sensor['min_critical'], 'yMax' => $sensor['min_critical'],
                'borderColor' => '#ff0000', 'borderWidth' => 1, 'borderDash' => [4, 4],
                'label' => ['display' => true, 'content' => 'Min Crit: '.$sensor['min_critical'],
                    'position' => 'start', 'backgroundColor' => 'rgba(255,255,255,0.8)',
                    'color' => '#ff0000', 'font' => ['size' => 8]],
            ];
        }
        if ($sensor['max_critical'] !== null) {
            $annotations['maxCritical'] = [
                'type' => 'line', 'yMin' => $sensor['max_critical'], 'yMax' => $sensor['max_critical'],
                'borderColor' => '#ff0000', 'borderWidth' => 1, 'borderDash' => [4, 4],
                'label' => ['display' => true, 'content' => 'Max Crit: '.$sensor['max_critical'],
                    'position' => 'start', 'backgroundColor' => 'rgba(255,255,255,0.8)',
                    'color' => '#ff0000', 'font' => ['size' => 8]],
            ];
        }
        if ($sensor['min_warning'] !== null) {
            $annotations['minWarning'] = [
                'type' => 'line', 'yMin' => $sensor['min_warning'], 'yMax' => $sensor['min_warning'],
                'borderColor' => '#ff8c00', 'borderWidth' => 1, 'borderDash' => [4, 4],
                'label' => ['display' => true, 'content' => 'Min Warn: '.$sensor['min_warning'],
                    'position' => 'end', 'backgroundColor' => 'rgba(255,255,255,0.8)',
                    'color' => '#ff8c00', 'font' => ['size' => 8]],
            ];
        }
        if ($sensor['max_warning'] !== null) {
            $annotations['maxWarning'] = [
                'type' => 'line', 'yMin' => $sensor['max_warning'], 'yMax' => $sensor['max_warning'],
                'borderColor' => '#ff8c00', 'borderWidth' => 1, 'borderDash' => [4, 4],
                'label' => ['display' => true, 'content' => 'Max Warn: '.$sensor['max_warning'],
                    'position' => 'end', 'backgroundColor' => 'rgba(255,255,255,0.8)',
                    'color' => '#ff8c00', 'font' => ['size' => 8]],
            ];
        }
    }
@endphp

<div class="chart-container">
    <canvas id="{{ $canvasId }}"></canvas>
</div>

<script>
    (function () {
        const ctx = document.getElementById('{{ $canvasId }}').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($timestamps),
                datasets: [{
                    label: '{{ $sensor['label'] }} ({{ $sensor['unit'] }})',
                    data: @json($values),
                    borderColor: '{{ $color }}',
                    backgroundColor: 'transparent',
                    borderWidth: 1.5,
                    pointRadius: 0,
                    tension: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { boxWidth: 12, padding: 8, font: { size: 8 } }
                    },
                    annotation: { annotations: @json($annotations) }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45, minRotation: 45,
                            font: { size: 7 }, maxTicksLimit: 12,
                            callback: function(val, idx) {
                                // Show every Nth label to avoid crowding
                                return this.getLabelForValue(val);
                            }
                        },
                        grid: { display: false }
                    },
                    y: {
                        beginAtZero: false,
                        ticks: { font: { size: 8 } },
                        title: {
                            display: true,
                            text: '{{ $sensor['label'] }} ({{ $sensor['unit'] }})',
                            font: { size: 8, weight: 'bold' }
                        }
                    }
                }
            }
        });
    })();
</script>
