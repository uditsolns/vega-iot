@php
    $canvasId = 'canvas_' . uniqid();
@endphp

<div class="chart-container">
    <canvas id="{{ $canvasId }}"></canvas>
</div>

<script>
    (function() {
        const ctx = document.getElementById('{{ $canvasId }}').getContext('2d');

        const chartData = {
            labels: @json(array_column($logs, 'timestamp')),
            datasets: [
                {
                    label: '{{ $leftAxis['label'] }}',
                    data: @json(array_column($logs, $leftAxis['dataKey'])),
                    borderColor: '{{ $leftAxis['color'] }}',
                    backgroundColor: 'rgba(0,0,0,0)',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0,
                    yAxisID: 'y'
                },
                {
                    label: '{{ $rightAxis['label'] }}',
                    data: @json(array_column($logs, $rightAxis['dataKey'])),
                    borderColor: '{{ $rightAxis['color'] }}',
                    backgroundColor: 'rgba(0,0,0,0)',
                    borderWidth: 2,
                    pointRadius: 0,
                    tension: 0,
                    yAxisID: 'y1'
                }
            ]
        };

        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            padding: 10,
                            font: { size: 9 }
                        }
                    },
                    annotation: {
                        annotations: {
                            @if(isset($leftAxis['min']))
                            leftMin: {
                                type: 'line',
                                yMin: {{ $leftAxis['min'] }},
                                yMax: {{ $leftAxis['min'] }},
                                yScaleID: 'y',
                                borderColor: '{{ $leftAxis['color'] }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Min: {{ $leftAxis['min'] }}',
                                    position: 'start',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $leftAxis['color'] }}',
                                    font: { size: 8, weight: 'bold' }
                                }
                            },
                            @endif
                                @if(isset($leftAxis['max']))
                            leftMax: {
                                type: 'line',
                                yMin: {{ $leftAxis['max'] }},
                                yMax: {{ $leftAxis['max'] }},
                                yScaleID: 'y',
                                borderColor: '{{ $leftAxis['color'] }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Max: {{ $leftAxis['max'] }}',
                                    position: 'start',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $leftAxis['color'] }}',
                                    font: { size: 8, weight: 'bold' }
                                }
                            },
                            @endif
                                @if(isset($rightAxis['min']))
                            rightMin: {
                                type: 'line',
                                yMin: {{ $rightAxis['min'] }},
                                yMax: {{ $rightAxis['min'] }},
                                yScaleID: 'y1',
                                borderColor: '{{ $rightAxis['color'] }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Min: {{ $rightAxis['min'] }}',
                                    position: 'end',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $rightAxis['color'] }}',
                                    font: { size: 8, weight: 'bold' }
                                }
                            },
                            @endif
                                @if(isset($rightAxis['max']))
                            rightMax: {
                                type: 'line',
                                yMin: {{ $rightAxis['max'] }},
                                yMax: {{ $rightAxis['max'] }},
                                yScaleID: 'y1',
                                borderColor: '{{ $rightAxis['color'] }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Max: {{ $rightAxis['max'] }}',
                                    position: 'end',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $rightAxis['color'] }}',
                                    font: { size: 8, weight: 'bold' }
                                }
                            }
                            @endif
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 45,
                            minRotation: 45,
                            font: { size: 7 },
                            maxTicksLimit: 10
                        },
                        grid: { display: false }
                    },
                    y: {
                        type: 'linear',
                        position: 'left',
                        ticks: { font: { size: 9 } },
                        title: {
                            display: true,
                            text: '{{ $leftAxis['label'] }}',
                            font: { size: 9, weight: 'bold' }
                        }
                    },
                    y1: {
                        type: 'linear',
                        position: 'right',
                        ticks: { font: { size: 9 } },
                        title: {
                            display: true,
                            text: '{{ $rightAxis['label'] }}',
                            font: { size: 9, weight: 'bold' }
                        },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        };

        new Chart(ctx, config);
    })();
</script>
