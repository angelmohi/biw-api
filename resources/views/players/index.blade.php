@extends('layouts.app')

@section('content')
<div class="row mb-5">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0 fw-bold text-white">
                            <i class="fas fa-users me-3"></i>Jugadores
                        </h3>
                        <small class="text-white-50">
                            <i class="fas fa-calendar me-1"></i>
                            Datos actualizados para {{ $today->format('d/m/Y') }}
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Increases and Decreases -->
    <div class="col-12 mb-4">
        <div class="row">
            <!-- Top 5 Increases -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-arrow-up me-2"></i>Top 5 Subidas del Día
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($topIncreases->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($topIncreases as $player)
                                    <a href="{{ route('players.show', $player->biwenger_player_id) }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center">
                                        <div class="me-3">
                                            <img src="https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/{{ $player->biwenger_player_id }}.png" 
                                                 alt="{{ $player->player_name }}" 
                                                 class="rounded-circle player-avatar"
                                                 width="50" height="50"
                                                 style="object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/50x50/6c757d/ffffff?text={{ strtoupper(substr($player->player_name, 0, 2)) }}'">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">{{ $player->player_name }}</h6>
                                            <small class="text-muted">
                                                Precio: {{ number_format($player->getPriceInEuros(), 0, ',', '.') }}€
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-success fs-6">
                                                +{{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No hay subidas registradas para hoy</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Top 5 Decreases -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-arrow-down me-2"></i>Top 5 Bajadas del Día
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        @if($topDecreases->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($topDecreases as $player)
                                    <a href="{{ route('players.show', $player->biwenger_player_id) }}" 
                                       class="list-group-item list-group-item-action d-flex align-items-center">
                                        <div class="me-3">
                                            <img src="https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/{{ $player->biwenger_player_id }}.png" 
                                                 alt="{{ $player->player_name }}" 
                                                 class="rounded-circle player-avatar"
                                                 width="50" height="50"
                                                 style="object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/50x50/6c757d/ffffff?text={{ strtoupper(substr($player->player_name, 0, 2)) }}'">
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 fw-bold">{{ $player->player_name }}</h6>
                                            <small class="text-muted">
                                                Precio: {{ number_format($player->getPriceInEuros(), 0, ',', '.') }}€
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-danger fs-6">
                                                {{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-4">
                                <i class="fas fa-chart-line fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No hay bajadas registradas para hoy</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- All Players Table -->
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-table me-2"></i>Todos los Jugadores - {{ $today->format('d/m/Y') }}
                </h5>
                <small class="text-muted">{{ $todaysPlayers->count() }} jugadores</small>
            </div>
            <div class="card-body">
                @if($todaysPlayers->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover" id="playersTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="60"></th>
                                    <th>Jugador</th>
                                    <th class="text-end">Precio</th>
                                    <th class="text-end">Incremento</th>
                                    <th class="text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($todaysPlayers as $player)
                                    <tr>
                                        <td class="text-center">
                                            <img src="https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/{{ $player->biwenger_player_id }}.png" 
                                                 alt="{{ $player->player_name }}" 
                                                 class="rounded-circle player-avatar-sm"
                                                 width="40" height="40"
                                                 style="object-fit: cover;"
                                                 onerror="this.src='https://via.placeholder.com/40x40/6c757d/ffffff?text={{ strtoupper(substr($player->player_name, 0, 2)) }}'">
                                        </td>
                                        <td data-order="{{ \Str::ascii($player->player_name) }}">
                                            <div>
                                                <h6 class="mb-0">{{ $player->player_name }}</h6>
                                                <small class="text-muted">ID: {{ $player->biwenger_player_id }}</small>
                                            </div>
                                        </td>
                                        <td class="text-end" data-order="{{ $player->getPriceInEuros() }}">
                                            <span class="fs-6 fw-bold">{{ number_format($player->getPriceInEuros(), 0, ',', '.') }}€</span>
                                        </td>
                                        <td class="text-end" data-order="{{ $player->getPriceIncrementInEuros() }}">
                                            @if($player->price_increment > 0)
                                                <span class="text-success fw-bold">
                                                    +{{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€
                                                </span>
                                            @elseif($player->price_increment < 0)
                                                <span class="text-danger fw-bold">
                                                    {{ number_format($player->getPriceIncrementInEuros(), 0, ',', '.') }}€
                                                </span>
                                            @else
                                                <span class="text-muted">
                                                    0€
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('players.show', $player->biwenger_player_id) }}" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-chart-line me-1"></i>Ver Gráfica
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="fas fa-database fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay datos disponibles</h5>
                        <p class="text-muted">Los datos de jugadores aparecerán aquí cuando se sincronicen</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
<style>
.player-avatar {
    transition: transform 0.2s ease;
}

.player-avatar:hover {
    transform: scale(1.1);
}

.list-group-item-action:hover {
    background-color: #f8f9fa;
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.badge {
    font-size: 0.875rem !important;
}
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Función para normalizar texto eliminando tildes y caracteres especiales
    function normalizeText(text) {
        return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
    }
    
    // Configurar tipo de ordenamiento personalizado para texto sin tildes
    $.fn.dataTable.ext.type.order['text-no-accents-pre'] = function(data) {
        return normalizeText(data);
    };

    $('#playersTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
        },
        "pageLength": 25,
        "order": [[ 3, "desc" ]],  // Order by price increment descending
        "columnDefs": [
            { 
                "orderable": false, 
                "targets": [0, 4] // Disable sorting for avatar and actions columns
            },
            {
                // Para la columna de nombre (index 1) - ordenamiento sin tildes
                "type": "text-no-accents",
                "targets": [1]
            }
        ],
        "responsive": true,
        "autoWidth": false,
        "lengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]]
    });
});
</script>
@endpush