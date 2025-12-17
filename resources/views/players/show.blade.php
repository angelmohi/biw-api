@extends('layouts.app')

@section('content')
<div class="row mb-5">
    <!-- Player Header -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="me-4">
                            <img src="https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/{{ $player->biwenger_player_id }}.png" 
                                 alt="{{ $player->player_name }}" 
                                 class="rounded-circle"
                                 width="80" height="80"
                                 style="object-fit: cover; border: 3px solid rgba(255,255,255,0.3);"
                                 onerror="this.src='https://via.placeholder.com/80x80/6c757d/ffffff?text={{ strtoupper(substr($player->player_name, 0, 2)) }}'">
                        </div>
                        <div>
                            <h3 class="mb-0 fw-bold text-white">{{ $player->player_name }}</h3>
                            <small class="text-white-50">ID: {{ $player->biwenger_player_id }}</small>
                            <br>
                            <small class="text-white-50">Slug: {{ $player->slug ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('players.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver a Jugadores
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Stats -->
    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-euro-sign fa-2x text-primary mb-2"></i>
                        <h4 class="fw-bold">{{ number_format($player->getPriceInEuros(), 0, ',', '.') }}€</h4>
                        <p class="text-muted mb-0">Precio Actual</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        @if($player->price_increment > 0)
                            <i class="fas fa-arrow-up fa-2x text-success mb-2"></i>
                            <h4 class="fw-bold text-success">+{{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€</h4>
                        @elseif($player->price_increment < 0)
                            <i class="fas fa-arrow-down fa-2x text-danger mb-2"></i>
                            <h4 class="fw-bold text-danger">{{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€</h4>
                        @else
                            <i class="fas fa-minus fa-2x text-muted mb-2"></i>
                            <h4 class="fw-bold text-muted">0€</h4>
                        @endif
                        <p class="text-muted mb-0">Último Incremento</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                        <h4 class="fw-bold">{{ $player->record_date->format('d/m/Y') }}</h4>
                        <p class="text-muted mb-0">Última Actualización</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Price Evolution Chart -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line me-2 text-primary"></i>
                        Evolución del Precio
                    </h5>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm chart-period-btn" data-days="7">7 días</button>
                        <button type="button" class="btn btn-outline-primary btn-sm chart-period-btn" data-days="15">15 días</button>
                        <button type="button" class="btn btn-outline-primary btn-sm chart-period-btn active" data-days="30">30 días</button>
                        <button type="button" class="btn btn-outline-primary btn-sm chart-period-btn" data-days="60">60 días</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <div class="chart-container">
                            <canvas id="priceChart" style="display: block; max-height: 400px;"></canvas>
                            <div id="chartError" class="text-center py-5" style="display: none;">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                <h6 class="text-muted">No hay datos suficientes para mostrar la gráfica</h6>
                                <p class="text-muted">La gráfica aparecerá cuando haya más datos históricos disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.chart-container {
    position: relative;
    height: 400px;
    width: 100%;
}

.chart-period-btn.active {
    background-color: var(--bs-primary);
    border-color: var(--bs-primary);
    color: white;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let priceChart;
const playerId = {{ $player->biwenger_player_id }};

// Initial chart data from server
const initialData = @json($priceHistory->map(function($record) {
    return [
        'date' => $record->record_date->format('d/m/Y'),
        'price' => $record->getPriceInEuros(),
        'increment' => $record->getPriceIncrementInEuros()
    ];
}));

console.log('Initial data:', initialData); // Debug line

function initializeChart(data) {
    const ctx = document.getElementById('priceChart').getContext('2d');
    
    if (priceChart) {
        priceChart.destroy();
    }

    console.log('Initializing chart with data:', data); // Debug line

    if (data.length === 0) {
        document.getElementById('priceChart').style.display = 'none';
        document.getElementById('chartError').style.display = 'block';
        console.log('No data available for chart');
        return;
    } else {
        document.getElementById('priceChart').style.display = 'block';
        document.getElementById('chartError').style.display = 'none';
    }

    const labels = data.map(item => item.date);
    const prices = data.map(item => parseFloat(item.price));
    const increments = data.map(item => parseFloat(item.increment));

    console.log('Chart labels:', labels);
    console.log('Chart prices:', prices);
    console.log('Chart increments:', increments);

    priceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Precio (€)',
                data: prices,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'rgb(102, 126, 234)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 5,
                pointHoverRadius: 8
            }, {
                label: 'Incremento Diario (€)',
                data: increments,
                borderColor: function(context) {
                    return 'rgb(239, 68, 68)'; // Default red, will be updated per point
                },
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                borderWidth: 2,
                fill: false,
                tension: 0.2,
                pointBackgroundColor: function(context) {
                    const value = context.parsed?.y || increments[context.dataIndex];
                    return value >= 0 ? 'rgb(34, 197, 94)' : 'rgb(239, 68, 68)';
                },
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                yAxisID: 'increment'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            scales: {
                y: {
                    beginAtZero: false,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Precio (€)'
                    },
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                },
                increment: {
                    type: 'linear',
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Incremento Diario (€)'
                    },
                    grid: {
                        drawOnChartArea: false,
                    },
                    ticks: {
                        callback: function(value) {
                            return (value >= 0 ? '+' : '') + new Intl.NumberFormat('es-ES', {
                                style: 'currency',
                                currency: 'EUR',
                                minimumFractionDigits: 0,
                                maximumFractionDigits: 0
                            }).format(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return 'Fecha: ' + context[0].label;
                        },
                        label: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Precio: ' + new Intl.NumberFormat('es-ES', {
                                    style: 'currency',
                                    currency: 'EUR',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(context.parsed.y);
                            } else {
                                const value = context.parsed.y;
                                const sign = value >= 0 ? '+' : '';
                                return 'Incremento: ' + sign + new Intl.NumberFormat('es-ES', {
                                    style: 'currency',
                                    currency: 'EUR',
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                }).format(value);
                            }
                        }
                    }
                },
                legend: {
                    display: true,
                    position: 'top'
                }
            }
        }
    });
}

// Initialize chart with initial data
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing chart...');
    initializeChart(initialData);
});

// Period buttons functionality
document.querySelectorAll('.chart-period-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const days = this.getAttribute('data-days');
        
        // Remove active class from all buttons
        document.querySelectorAll('.chart-period-btn').forEach(b => b.classList.remove('active'));
        // Add active class to clicked button
        this.classList.add('active');
        
        // Fetch new data
        fetchChartData(days);
    });
});

function fetchChartData(days) {
    console.log('Fetching chart data for', days, 'days');
    fetch(`{{ route('players.chart-data', $player->biwenger_player_id) }}?days=${days}`)
        .then(response => response.json())
        .then(data => {
            console.log('Received data:', data);
            const chartData = data.labels.map((label, index) => ({
                date: label,
                price: data.prices[index],
                increment: data.increments[index]
            }));
            console.log('Processed chart data:', chartData);
            initializeChart(chartData);
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
        });
}
</script>
@endpush