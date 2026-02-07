@php
    $chartId = 'chart_' . uniqid();
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
            datasets: [{
                label: '{{ $label }}',
                data: @json(array_column($logs, $dataKey)),
                borderColor: '{{ $color }}',
                backgroundColor: 'rgba(0,0,0,0)',
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 0,
                tension: 0,
                fill: false
            }]
        };

        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
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
                            @if(isset($minThreshold))
                            minLine: {
                                type: 'line',
                                yMin: {{ $minThreshold }},
                                yMax: {{ $minThreshold }},
                                borderColor: '{{ $color }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Min: {{ $minThreshold }}',
                                    position: 'start',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $color }}',
                                    font: { size: 9, weight: 'bold' }
                                }
                            },
                            @endif
                                @if(isset($maxThreshold))
                            maxLine: {
                                type: 'line',
                                yMin: {{ $maxThreshold }},
                                yMax: {{ $maxThreshold }},
                                borderColor: '{{ $color }}',
                                borderWidth: 1,
                                borderDash: [5, 5],
                                label: {
                                    display: true,
                                    content: 'Max: {{ $maxThreshold }}',
                                    position: 'start',
                                    backgroundColor: 'rgba(255,255,255,0.8)',
                                    color: '{{ $color }}',
                                    font: { size: 9, weight: 'bold' }
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
                        beginAtZero: false,
                        ticks: {
                            font: { size: 9 }
                        },
                        title: {
                            display: true,
                            text: '{{ $label }}',
                            font: { size: 10, weight: 'bold' }
                        }
                    }
                }
            }
        };

        new Chart(ctx, config);
    })();
</script>
