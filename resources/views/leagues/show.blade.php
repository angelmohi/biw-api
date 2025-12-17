@extends('layouts.app')

@section('content')

<div class="row mb-5">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header league-header-detail" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%) !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="mb-0 fw-bold text-white">
                            <i class="fas fa-trophy me-3"></i>{{ $league->name }}
                        </h3>
                        <small class="text-white-50">
                            <i class="fas fa-calendar me-1"></i>
                            Creada el {{ $league->created_at->format('d/m/Y') }}
                        </small>
                    </div>
                    <div>
                        <a href="{{ route('leagues.index') }}" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        @if(Auth::user()->isFullAdministrator())
                        <form method="POST" action="{{ route('leagues.update', $league->id) }}" class="d-inline ms-2">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="btn btn-light" id="update-league-btn">
                                <span class="btn-text">
                                    <i class="fas fa-sync me-2"></i>Actualizar liga
                                </span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Actualizando...
                                </span>
                            </button>
                        </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 mb-4">
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2" style="color: #3498db;"></i>
                        <h4 class="fw-bold">{{ $league->biwengerUsers->count() }}</h4>
                        <p class="text-muted mb-0">Participantes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-2x mb-2" style="color: #1abc9c;"></i>
                        <h4 class="fw-bold">{{ $league->getTotalTransfers() }}</h4>
                        <p class="text-muted mb-0">Fichajes realizados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-user-minus fa-2x mb-2" style="color: #2c3e50;"></i>
                        <h4 class="fw-bold">{{ $league->getTotalSales() }}</h4>
                        <p class="text-muted mb-0">Ventas realizadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x mb-2" style="color: #34495e;"></i>
                        <h4 class="fw-bold">{{ $league->updated_at->diffForHumans() }}</h4>
                        <p class="text-muted mb-0">Última actualización</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <!-- Desktop card wrapper -->
        <div class="card d-none d-lg-block">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" id="leagueTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="classification-tab" data-bs-toggle="tab" data-bs-target="#classification" type="button" role="tab" aria-controls="classification" aria-selected="true">
                            <i class="fas fa-list-ol me-2"></i>Clasificación
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transactions-tab" data-bs-toggle="tab" data-bs-target="#transactions" type="button" role="tab" aria-controls="transactions" aria-selected="false">
                            <i class="fas fa-exchange-alt me-2"></i>Transacciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="balance-chart-tab" data-bs-toggle="tab" data-bs-target="#balance-chart" type="button" role="tab" aria-controls="balance-chart" aria-selected="false">
                            <i class="fas fa-chart-line me-2"></i>Evolución Balance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="user-transfers-tab" data-bs-toggle="tab" data-bs-target="#user-transfers" type="button" role="tab" aria-controls="user-transfers" aria-selected="false">
                            <i class="fas fa-handshake me-2"></i>Traspasos entre usuarios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="transfer-profits-tab" data-bs-toggle="tab" data-bs-target="#transfer-profits" type="button" role="tab" aria-controls="transfer-profits" aria-selected="false">
                            <i class="fas fa-coins me-2"></i>Ganancias por Ventas
                        </button>
                    </li>
                    @if(Auth::user()->isFullAdministrator())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="purchase-analysis-tab" data-bs-toggle="tab" data-bs-target="#purchase-analysis" type="button" role="tab" aria-controls="purchase-analysis" aria-selected="false">
                            <i class="fas fa-chart-bar me-2"></i>Análisis de Compras
                        </button>
                    </li>
                    @endif
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="leagueTabsContent">
                    <div class="tab-pane fade show active" id="classification" role="tabpanel" aria-labelledby="classification-tab">
                        @if($league->biwengerUsers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="rankingTable">
                                    <thead style="background-color: #ecf0f1;">
                                        <tr>
                                            <th class="text-center sortable" width="50" data-column="position">
                                                Posición
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="180" data-column="name">
                                                Jugador
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="80" data-column="points">
                                                Puntos
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="120" data-column="team_value">
                                                Valor Equipo
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="120" data-column="cash">
                                                Dinero
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="120" data-column="maximum_bid">
                                                Puja Máxima
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                            <th class="sortable" width="120" data-column="balance">
                                                Balance Total
                                                <i class="fas fa-sort ms-1"></i>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($league->biwengerUsers->filter(function($user) { return $user->position > 0 && $user->points > 0; }) as $user)
                                            @php
                                                $currentBalance = $user->balances->sortByDesc('created_at')->first();
                                                $hasNegativeCash = $currentBalance && $currentBalance->cash < 0;
                                            @endphp
                                            <tr class="ranking-row position-{{ $user->position }} {{ $hasNegativeCash ? 'negative-cash' : '' }}" 
                                                data-position="{{ $user->position }}"
                                                data-name="{{ $user->name }}"
                                                data-points="{{ $user->points }}"
                                                data-team-value="{{ $currentBalance?->team_value ?? 0 }}"
                                                data-cash="{{ $currentBalance?->cash ?? 0 }}"
                                                data-maximum-bid="{{ $currentBalance?->maximum_bid ?? 0 }}"
                                                data-balance="{{ $currentBalance?->balance ?? 0 }}"
                                                @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                <td class="text-center align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    @if($user->position <= 3)
                                                        <i class="fas fa-medal medal-{{ $user->position }} fa-lg"></i>
                                                    @else
                                                        <span class="badge bg-secondary">{{ $user->position }}</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    <div class="d-flex align-items-center">
                                                        <div class="player-avatar me-3">
                                                            @if($user->icon)
                                                                <img src="{{ $user->icon }}" alt="{{ $user->name }}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                                            @else
                                                                {{ strtoupper(substr($user->name, 0, 2)) }}
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h6 class="mb-0">{{ $user->name }}</h6>
                                                            <small class="text-muted">ID: {{ $user->biwenger_id }}</small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    <span class="fs-5">{{ number_format($user->points, 0, ',', '.') }} <small class="text-muted">pts</small></span>
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    @if($currentBalance)
                                                        <span>{{ number_format($currentBalance->team_value, 0, ',', '.') }} <small class="text-muted">€</small></span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    @if($currentBalance)
                                                        <span>{{ number_format($currentBalance->cash, 0, ',', '.') }} <small class="text-muted">€</small></span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    @if($currentBalance)
                                                        <span>{{ number_format($currentBalance->maximum_bid, 0, ',', '.') }} <small class="text-muted">€</small></span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle" @if($hasNegativeCash) style="background-color: #f8d7da !important;" @endif>
                                                    @if($currentBalance)
                                                        <span>{{ number_format($currentBalance->balance, 0, ',', '.') }} <small class="text-muted">€</small></span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay participantes en esta liga</h5>
                                <p class="text-muted">Los participantes aparecerán aquí cuando se sincronicen los datos</p>
                            </div>
                        @endif
                    </div>

                    <div class="tab-pane fade" id="transactions" role="tabpanel" aria-labelledby="transactions-tab">
                        <div class="p-3">
                            <table class="table table-hover mb-0" id="transactionsTable">
                                <thead style="background-color: #ecf0f1;">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Jugador</th>
                                        <th>De</th>
                                        <th>Para</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="user-transfers" role="tabpanel" aria-labelledby="user-transfers-tab">
                        <div class="p-4">
                            @php
                                $userTransfers = $league->getUserTransfers();
                            @endphp
                            
                            @if($userTransfers->count() > 0)
                                <!-- Header Statistics -->
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #360033 0%, #0b8793 100%);">
                                            <div class="card-body text-white py-4">
                                                <div class="row text-center">
                                                    <div class="col-md-4">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-handshake fa-2x me-3"></i>
                                                            <div class="text-start">
                                                                <h2 class="mb-0 fw-bold">{{ $league->getTotalUserTransfers() }}</h2>
                                                                <small class="opacity-75">Traspasos totales</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 border-start border-white border-opacity-25">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-users fa-2x me-3"></i>
                                                            <div class="text-start">
                                                                <h2 class="mb-0 fw-bold">{{ $userTransfers->count() }}</h2>
                                                                <small class="opacity-75">Parejas activas</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 border-start border-white border-opacity-25">
                                                        <div class="d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-coins fa-2x me-3"></i>
                                                            <div class="text-start">
                                                                @php
                                                                    $totalAmount = $userTransfers->flatMap(function($data) {
                                                                        return $data['transfers'];
                                                                    })->sum('amount');
                                                                @endphp
                                                                <h2 class="mb-0 fw-bold">{{ number_format($totalAmount / 1000000, 1) }}M€</h2>
                                                                <small class="opacity-75">Valor total movido</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfers Grid -->
                                <div class="row g-4">
                                    @foreach($userTransfers as $transferData)
                                        @php
                                            $totalValue = $transferData['transfers']->sum('amount');
                                            $avgValue = $transferData['transfers']->avg('amount');
                                            $users = explode(' ↔ ', $transferData['users_pair']);
                                        @endphp
                                        <div class="col-lg-6 col-xl-4">
                                            <div class="card border-0 shadow-sm hover-lift mb-3">
                                                <!-- Collapsible Header -->
                                                <div class="card-header border-0 text-white collapsed" 
                                                     style="cursor: pointer; background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);" 
                                                     data-bs-toggle="collapse" 
                                                     data-bs-target="#transfers-{{ $loop->index }}" 
                                                     aria-expanded="false">
                                                    <div class="d-flex justify-content-between align-items-center py-2">
                                                        <div class="d-flex align-items-center flex-grow-1">
                                                            <i class="fas fa-exchange-alt fa-lg me-3"></i>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold">{{ $transferData['users_pair'] }}</h6>
                                                                <small class="opacity-75">{{ $transferData['total_transfers'] }} {{ $transferData['total_transfers'] == 1 ? 'traspaso' : 'traspasos' }} • {{ number_format($totalValue, 0, ',', '.') }}€</small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <i class="fas fa-chevron-down transition-rotate"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Collapsible Body with Transfers -->
                                                <div id="transfers-{{ $loop->index }}" class="collapse">
                                                    <div class="card-body p-0">
                                                        @foreach($transferData['transfers'] as $transfer)
                                                            @php
                                                                $playerPrice = $transfer->getFormattedPlayerPrice();
                                                                $playerImageUrl = $transfer->player_id 
                                                                    ? 'https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/' . $transfer->player_id . '.png'
                                                                    : '';
                                                                $fallbackImage = 'https://via.placeholder.com/50x50/6c757d/ffffff?text=' . urlencode(strtoupper(substr($transfer->player_name ?? 'P', 0, 2)));
                                                            @endphp
                                                            <div class="transfer-row p-3 border-bottom hover-bg">
                                                                <div class="d-flex align-items-start gap-3">
                                                                    <!-- Player Image -->
                                                                    @if($transfer->player_id)
                                                                        <div class="flex-shrink-0">
                                                                            <img src="{{ $playerImageUrl }}" 
                                                                                 alt="{{ $transfer->player_name ?? 'Jugador' }}" 
                                                                                 class="rounded-circle border border-2 border-light shadow-sm" 
                                                                                 width="50" 
                                                                                 height="50" 
                                                                                 style="object-fit: cover;"
                                                                                 onerror="this.src='{{ $fallbackImage }}'">
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <!-- Transfer Details -->
                                                                    <div class="flex-grow-1 min-w-0">
                                                                        <div class="d-flex justify-content-between align-items-start mb-1">
                                                                            <h6 class="mb-0 fw-bold text-truncate">
                                                                                {{ $transfer->player_name ?? 'Jugador' }}
                                                                            </h6>
                                                                            <span class="badge text-white ms-2 flex-shrink-0" style="background-color: #1abc9c;">
                                                                                {{ number_format($transfer->amount, 0, ',', '.') }}€
                                                                            </span>
                                                                        </div>
                                                                        
                                                                        <!-- Transfer Direction -->
                                                                        <div class="d-flex align-items-center gap-2 mb-1">
                                                                            <span class="badge bg-danger bg-opacity-10 text-danger small">
                                                                                {{ $transfer->userFrom->name }}
                                                                            </span>
                                                                            <i class="fas fa-arrow-right text-muted small"></i>
                                                                            <span class="badge text-white small" style="background-color: rgba(26, 188, 156, 0.2); color: #1abc9c !important;">
                                                                                {{ $transfer->userTo->name }}
                                                                            </span>
                                                                        </div>
                                                                        
                                                                        <!-- Market Value and Date -->
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            @if($playerPrice)
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-chart-line me-1"></i>
                                                                                    VM: {{ $playerPrice['formatted_price'] }}
                                                                                    @if($playerPrice['price_increment'] != 0)
                                                                                        <span class="text-{{ $playerPrice['price_increment'] >= 0 ? 'success' : 'danger' }}">
                                                                                            ({{ $playerPrice['formatted_increment'] }})
                                                                                        </span>
                                                                                    @endif
                                                                                </small>
                                                                            @else
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-chart-line me-1"></i>VM: No disponible
                                                                                </small>
                                                                            @endif
                                                                            <small class="text-muted">
                                                                                <i class="fas fa-calendar-alt me-1"></i>{{ $transfer->date->format('d/m/Y') }}
                                                                            </small>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div class="mb-4">
                                        <i class="fas fa-handshake fa-4x text-muted opacity-50"></i>
                                    </div>
                                    <h4 class="text-muted mb-2">No hay traspasos entre usuarios</h4>
                                    <p class="text-muted">Los traspasos entre usuarios aparecerán aquí cuando se realicen transacciones</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="tab-pane fade" id="balance-chart" role="tabpanel" aria-labelledby="balance-chart-tab">
                        <div class="p-5">
                            <div class="row">
                                <div class="col-12 mb-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-chart-line me-2" style="color: #3498db;"></i>
                                            Evolución del Balance Total
                                        </h5>
                                        <div class="chart-controls">
                                            <button class="btn btn-sm text-white" style="background-color: #3498db;" id="toggleLegend">
                                                <i class="fas fa-eye-slash me-1"></i>Ocultar Leyenda
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Historial de balance (dinero + valor del equipo) desde el inicio de la liga
                                    </small>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-12">
                                    <div class="chart-container p-3">
                                        <canvas id="balanceChart" style="display: block;"></canvas>
                                        <div id="chartError" class="text-center py-5" style="display: none;">
                                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                            <h6 class="text-muted">No hay datos suficientes para mostrar la gráfica</h6>
                                            <p class="text-muted">La gráfica aparecerá cuando haya datos de balance histórico disponibles</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(Auth::user()->isFullAdministrator())
                    <div class="tab-pane fade" id="purchase-analysis" role="tabpanel" aria-labelledby="purchase-analysis-tab">
                        <div class="p-3">
                            @php
                                $purchaseAnalysis = $league->getPurchaseAnalysis();
                            @endphp
                            
                            @if($purchaseAnalysis->count() > 0)
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="alert" style="background-color: #d6eaf8; border-color: #3498db; color: #21618c;">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Análisis de Compras:</strong> Porcentaje mediano de sobrepago respecto al valor de mercado de cada jugador en el momento de la compra.
                                            <br><small><strong>Nota:</strong> Solo se muestran usuarios con compras que tienen datos de valor de mercado disponibles. Las compras sin datos de mercado no se incluyen en el análisis ni en los gastos. La mediana elimina el efecto de valores extremos.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="purchaseAnalysisTable">
                                                <thead style="background-color: #ecf0f1;">
                                                    <tr>
                                                        <th width="50">#</th>
                                                        <th>Usuario</th>
                                                        <th class="text-center">Total Fichajes</th>
                                                        <th class="text-end">Total Pagado</th>
                                                        <th class="text-end">Valor de Mercado</th>
                                                        <th class="text-end">Sobrepago Total</th>
                                                        <th class="text-center">% Mediana Sobrepago</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($purchaseAnalysis as $index => $analysis)
                                                        <tr>
                                                            <td class="text-center">
                                                                <span class="badge bg-secondary">{{ $index + 1 }}</span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="player-avatar me-3">
                                                                        @if($analysis['user']->icon)
                                                                            <img src="{{ $analysis['user']->icon }}" alt="{{ $analysis['user_name'] }}" class="rounded-circle" width="35" height="35" style="object-fit: cover;">
                                                                        @else
                                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 14px;">
                                                                                {{ strtoupper(substr($analysis['user_name'], 0, 2)) }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0">{{ $analysis['user_name'] }}</h6>
                                                                        <small class="text-muted">ID: {{ $analysis['user']->biwenger_id }}</small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center" data-sort="{{ $analysis['total_purchases'] }}">
                                                                <span class="badge text-white" style="background-color: #3498db;">{{ $analysis['total_purchases'] }}</span>
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_amount_paid'] }}">
                                                                <span class="fw-bold">{{ number_format($analysis['total_amount_paid'], 0, ',', '.') }}€</span>
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_market_value'] }}">
                                                                <span class="text-muted">{{ number_format($analysis['total_market_value'], 0, ',', '.') }}€</span>
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_overpay_amount'] }}">
                                                                <span class="fw-bold {{ $analysis['total_overpay_amount'] >= 0 ? 'text-danger' : 'text-success' }}">
                                                                    {{ $analysis['total_overpay_amount'] >= 0 ? '+' : '' }}{{ number_format($analysis['total_overpay_amount'], 0, ',', '.') }}€
                                                                </span>
                                                            </td>
                                                            <td class="text-center" data-sort="{{ $analysis['average_overpay_percentage'] }}">
                                                                <span class="badge {{ $analysis['average_overpay_percentage'] >= 0 ? 'bg-danger' : 'bg-success' }} fs-6">
                                                                    {{ $analysis['average_overpay_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_overpay_percentage'], 1) }}%
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Individual tabs for each user's purchases -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h5 class="mb-3">
                                            <i class="fas fa-list me-2"></i>Compras por Usuario
                                        </h5>
                                        <small class="text-muted">Cada tabla ordenada por cantidad absoluta de sobrepago/ahorro con respecto al valor de mercado</small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <!-- User tabs navigation -->
                                        <ul class="nav nav-tabs" id="userPurchasesTabs" role="tablist">
                                            @foreach($purchaseAnalysis as $index => $analysis)
                                                <li class="nav-item" role="presentation">
                                                    <button class="nav-link {{ $index === 0 ? 'active' : '' }}" id="user-{{ $analysis['user']->biwenger_id }}-tab" data-bs-toggle="tab" data-bs-target="#user-{{ $analysis['user']->biwenger_id }}" type="button" role="tab" aria-controls="user-{{ $analysis['user']->biwenger_id }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}">
                                                        <div class="d-flex align-items-center">
                                                            <div class="player-avatar me-2">
                                                                @if($analysis['user']->icon)
                                                                    <img src="{{ $analysis['user']->icon }}" alt="{{ $analysis['user_name'] }}" class="rounded-circle" width="20" height="20" style="object-fit: cover;">
                                                                @else
                                                                    <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 20px; height: 20px; font-size: 10px;">
                                                                        {{ strtoupper(substr($analysis['user_name'], 0, 2)) }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <span>{{ $analysis['user_name'] }}</span>
                                                            <span class="badge {{ $analysis['average_overpay_percentage'] >= 0 ? 'bg-danger' : 'bg-success' }} ms-2">
                                                                {{ $analysis['average_overpay_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_overpay_percentage'], 1) }}%
                                                            </span>
                                                        </div>
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>

                                        <!-- Tab content -->
                                        <div class="tab-content" id="userPurchasesTabContent">
                                            @foreach($purchaseAnalysis as $index => $analysis)
                                                @php
                                                    // Order purchases by absolute overpay amount (highest impact first)
                                                    $sortedPurchases = collect($analysis['purchases'])->sortByDesc(function($purchase) {
                                                        return abs($purchase['overpay_amount']);
                                                    });
                                                @endphp
                                                
                                                <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="user-{{ $analysis['user']->biwenger_id }}" role="tabpanel" aria-labelledby="user-{{ $analysis['user']->biwenger_id }}-tab">
                                                    <div class="border border-top-0 p-3">
                                                        <!-- User summary info -->
                                                        <div class="row mb-3">
                                                            <div class="col-12">
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    <div class="d-flex align-items-center">
                                                                        <div class="player-avatar me-3">
                                                                            @if($analysis['user']->icon)
                                                                                <img src="{{ $analysis['user']->icon }}" alt="{{ $analysis['user_name'] }}" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                                                            @else
                                                                                <div class="rounded-circle text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; font-size: 16px; background-color: #3498db;">
                                                                                    {{ strtoupper(substr($analysis['user_name'], 0, 2)) }}
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                        <div>
                                                                            <h6 class="mb-0">{{ $analysis['user_name'] }}</h6>
                                                                            <small class="text-muted">{{ $analysis['total_purchases'] }} compras analizadas</small>
                                                                        </div>
                                                                    </div>
                                                                    <div class="text-end">
                                                                        <div class="row g-2">
                                                                            <div class="col-auto">
                                                                                <small class="text-muted d-block">Total Pagado</small>
                                                                                <strong>{{ number_format($analysis['total_amount_paid'], 0, ',', '.') }}€</strong>
                                                                            </div>
                                                                            <div class="col-auto">
                                                                                <small class="text-muted d-block">Valor Mercado</small>
                                                                                <span class="text-muted">{{ number_format($analysis['total_market_value'], 0, ',', '.') }}€</span>
                                                                            </div>
                                                                            <div class="col-auto">
                                                                                <small class="text-muted d-block">Diferencia</small>
                                                                                <span class="fw-bold {{ $analysis['total_overpay_amount'] >= 0 ? 'text-danger' : 'text-success' }}">
                                                                                    {{ $analysis['total_overpay_amount'] >= 0 ? '+' : '' }}{{ number_format($analysis['total_overpay_amount'], 0, ',', '.') }}€
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- User purchases table -->
                                                        @if($sortedPurchases->count() > 0)
                                                            <div class="table-responsive">
                                                                <table class="table table-hover user-purchases-table">
                                                                    <thead style="background-color: #ecf0f1;">
                                                                        <tr>
                                                                            <th width="40">#</th>
                                                                            <th>Jugador</th>
                                                                            <th class="text-center">Fecha</th>
                                                                            <th class="text-end">Precio Pagado</th>
                                                                            <th class="text-end">Valor de Mercado</th>
                                                                            <th class="text-end">Tendencia Día Anterior</th>
                                                                            <th class="text-end">Diferencia</th>
                                                                            <th class="text-center">% Diferencia</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        @foreach($sortedPurchases as $purchaseIndex => $purchase)
                                                                            <tr>
                                                                                <td class="text-center">
                                                                                    <span class="badge bg-secondary">{{ $purchaseIndex + 1 }}</span>
                                                                                </td>
                                                                                <td>
                                                                                    <strong>{{ $purchase['player_name'] }}</strong>
                                                                                </td>
                                                                                <td class="text-center" data-sort="{{ $purchase['date']->format('Y-m-d') }}">
                                                                                    <small>{{ $purchase['date']->format('d/m/Y') }}</small>
                                                                                </td>
                                                                                <td class="text-end" data-sort="{{ $purchase['amount_paid'] }}">
                                                                                    <span class="fw-bold">{{ number_format($purchase['amount_paid'], 0, ',', '.') }}€</span>
                                                                                </td>
                                                                                <td class="text-end" data-sort="{{ $purchase['market_value'] }}">
                                                                                    <span class="text-muted">{{ number_format($purchase['market_value'], 0, ',', '.') }}€</span>
                                                                                </td>
                                                                                <td class="text-end" data-sort="{{ $purchase['price_change'] ?? 0 }}">
                                                                                    @if($purchase['price_change'] !== null)
                                                                                        <div class="d-flex flex-column align-items-end">
                                                                                            <span class="small {{ $purchase['price_change'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                                                {{ $purchase['price_change'] >= 0 ? '+' : '' }}{{ number_format($purchase['price_change'], 0, ',', '.') }}€
                                                                                            </span>
                                                                                            <span class="badge {{ $purchase['price_change_percentage'] >= 0 ? 'bg-success' : 'bg-danger' }}" style="font-size: 0.7em;">
                                                                                                {{ $purchase['price_change_percentage'] >= 0 ? '+' : '' }}{{ number_format($purchase['price_change_percentage'], 1) }}%
                                                                                            </span>
                                                                                        </div>
                                                                                    @else
                                                                                        <span class="text-muted small">Sin datos</span>
                                                                                    @endif
                                                                                </td>
                                                                                <td class="text-end" data-sort="{{ $purchase['overpay_amount'] }}">
                                                                                    <span class="fw-bold {{ $purchase['overpay_amount'] >= 0 ? 'text-danger' : 'text-success' }}">
                                                                                        {{ $purchase['overpay_amount'] >= 0 ? '+' : '' }}{{ number_format($purchase['overpay_amount'], 0, ',', '.') }}€
                                                                                    </span>
                                                                                </td>
                                                                                <td class="text-center" data-sort="{{ $purchase['overpay_percentage'] }}">
                                                                                    <span class="badge {{ $purchase['overpay_percentage'] >= 0 ? 'bg-danger' : 'bg-success' }}">
                                                                                        {{ $purchase['overpay_percentage'] >= 0 ? '+' : '' }}{{ number_format($purchase['overpay_percentage'], 1) }}%
                                                                                    </span>
                                                                                </td>
                                                                            </tr>
                                                                        @endforeach
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        @else
                                                            <div class="text-center py-4">
                                                                <i class="fas fa-shopping-cart fa-2x text-muted mb-2"></i>
                                                                <p class="text-muted mb-0">No hay compras con datos de mercado disponibles</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay datos de compras disponibles</h5>
                                    <p class="text-muted">Los análisis aparecerán cuando haya transacciones con datos de mercado históricos</p>
                                </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    <div class="tab-pane fade" id="transfer-profits" role="tabpanel" aria-labelledby="transfer-profits-tab">
                        <div class="p-3">
                            @php
                                $transferProfitAnalysis = $league->getTransferProfitAnalysis();
                            @endphp
                            
                            @if($transferProfitAnalysis->count() > 0)
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="alert" style="background-color: #d5f4e6; border-color: #1abc9c; color: #0e6251;">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Ganancias por Ventas:</strong> Análisis de beneficios obtenidos por cada usuario al vender jugadores a otros usuarios de la liga.
                                            <br><small><strong>Nota:</strong> Se compara el precio de venta con el valor de mercado del jugador en la fecha de venta para determinar si fue una operación beneficiosa.</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Table for desktop only -->
                                <div class="row d-none d-lg-block">
                                    <div class="col-12">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="transferProfitsTable">
                                                <thead style="background-color: #ecf0f1;">
                                                    <tr>
                                                        <th>Usuario</th>
                                                        <th class="text-center">Total Ventas</th>
                                                        <th class="text-center">Con Análisis</th>
                                                        <th class="text-end">Ingresos Totales</th>
                                                        <th class="text-end">Valor de Mercado</th>
                                                        <th class="text-end">Ganancias Totales</th>
                                                        <th class="text-center">% Promedio Ganancia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($transferProfitAnalysis as $index => $analysis)
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <div class="player-avatar me-3">
                                                                        @if($analysis['user']->icon)
                                                                            <img src="{{ $analysis['user']->icon }}" alt="{{ $analysis['user_name'] }}" class="rounded-circle" width="35" height="35" style="object-fit: cover;">
                                                                        @else
                                                                            <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center" style="width: 35px; height: 35px; font-size: 14px;">
                                                                                {{ strtoupper(substr($analysis['user_name'], 0, 2)) }}
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                    <div>
                                                                        <h6 class="mb-0">{{ $analysis['user_name'] }}</h6>
                                                                        <small class="text-muted">ID: {{ $analysis['user']->biwenger_id }}</small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="text-center" data-sort="{{ $analysis['total_sales'] }}">
                                                                <span class="badge text-white" style="background-color: #3498db;">{{ $analysis['total_sales'] }}</span>
                                                            </td>
                                                            <td class="text-center" data-sort="{{ $analysis['sales_with_market_data'] ?? 0 }}">
                                                                <span class="badge text-white" style="background-color: #1abc9c;">{{ $analysis['sales_with_market_data'] ?? 0 }}</span>
                                                                @if(($analysis['sales_with_market_data'] ?? 0) < $analysis['total_sales'])
                                                                    <br><small class="text-muted">{{ $analysis['total_sales'] - ($analysis['sales_with_market_data'] ?? 0) }} sin datos</small>
                                                                @endif
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_revenue'] }}">
                                                                <span class="fw-bold">{{ number_format($analysis['total_revenue'], 0, ',', '.') }}€</span>
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_market_value'] }}">
                                                                <span class="text-muted">{{ number_format($analysis['total_market_value'], 0, ',', '.') }}€</span>
                                                            </td>
                                                            <td class="text-end" data-sort="{{ $analysis['total_profit'] }}">
                                                                <span class="fw-bold {{ $analysis['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                                    {{ $analysis['total_profit'] >= 0 ? '+' : '' }}{{ number_format($analysis['total_profit'], 0, ',', '.') }}€
                                                                </span>
                                                            </td>
                                                            <td class="text-center" data-sort="{{ $analysis['average_profit_percentage'] }}">
                                                                <span class="badge {{ $analysis['average_profit_percentage'] >= 0 ? 'bg-success' : 'bg-danger' }} fs-6">
                                                                    {{ $analysis['average_profit_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_profit_percentage'], 1) }}%
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Detailed sales section title (desktop only) -->
                                <div class="row mt-4 d-none d-lg-block">
                                    <div class="col-12">
                                        <h5 class="mb-3">
                                            <i class="fas fa-search me-2"></i>Ventas Detalladas por Usuario
                                        </h5>
                                        <small class="text-muted">Haz clic en cada tarjeta para ver todas las ventas del usuario</small>
                                    </div>
                                </div>

                                <!-- Transfers Grid -->
                                <div class="row g-4 mt-3">
                                    @foreach($transferProfitAnalysis as $analysis)
                                        @php
                                            // Order sales by absolute profit amount (highest impact first)
                                            $sortedSales = collect($analysis['sales'])->sortByDesc(function($sale) {
                                                return $sale['profit_amount'] !== null ? abs($sale['profit_amount']) : 0;
                                            });
                                            $totalSales = $sortedSales->count();
                                        @endphp
                                        <div class="col-lg-6 col-xl-4">
                                            <div class="card border-0 shadow-sm hover-lift mb-3">
                                                <!-- Collapsible Header -->
                                                <div class="card-header border-0 text-white collapsed" 
                                                     style="cursor: pointer; background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);" 
                                                     data-bs-toggle="collapse" 
                                                     data-bs-target="#sales-{{ $analysis['user']->biwenger_id }}" 
                                                     aria-expanded="false">
                                                    <div class="d-flex justify-content-between align-items-center py-2">
                                                        <div class="d-flex align-items-center flex-grow-1">
                                                            <i class="fas fa-coins fa-lg me-3"></i>
                                                            <div>
                                                                <h6 class="mb-0 fw-bold">{{ $analysis['user_name'] }}</h6>
                                                                <small class="opacity-75">{{ $totalSales }} {{ $totalSales == 1 ? 'venta' : 'ventas' }} • {{ number_format($analysis['total_revenue'], 0, ',', '.') }}€</small>
                                                            </div>
                                                        </div>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-light text-dark me-2">
                                                                {{ $analysis['average_profit_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_profit_percentage'], 1) }}%
                                                            </span>
                                                            <i class="fas fa-chevron-down transition-rotate"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Collapsible Body with Sales -->
                                                <div id="sales-{{ $analysis['user']->biwenger_id }}" class="collapse">
                                                    <div class="card-body p-0">
                                                        @if($sortedSales->count() > 0)
                                                            @foreach($sortedSales as $sale)
                                                                @php
                                                                    $playerImageUrl = $sale['player_id'] 
                                                                        ? 'https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/' . $sale['player_id'] . '.png'
                                                                        : '';
                                                                    $fallbackImage = 'https://via.placeholder.com/50x50/6c757d/ffffff?text=' . urlencode(strtoupper(substr($sale['player_name'] ?? 'P', 0, 2)));
                                                                @endphp
                                                                <div class="transfer-row p-3 border-bottom hover-bg">
                                                                    <div class="d-flex align-items-start gap-3">
                                                                        <!-- Player Image -->
                                                                        @if($sale['player_id'])
                                                                            <div class="flex-shrink-0">
                                                                                <img src="{{ $playerImageUrl }}" 
                                                                                     alt="{{ $sale['player_name'] }}" 
                                                                                     class="rounded-circle border border-2 border-light shadow-sm" 
                                                                                     width="50" 
                                                                                     height="50" 
                                                                                     style="object-fit: cover;"
                                                                                     onerror="this.src='{{ $fallbackImage }}'">
                                                                            </div>
                                                                        @endif
                                                                        
                                                                        <!-- Sale Details -->
                                                                        <div class="flex-grow-1 min-w-0">
                                                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                                                <h6 class="mb-0 fw-bold text-truncate">
                                                                                    {{ $sale['player_name'] }}
                                                                                </h6>
                                                                                <span class="badge text-white ms-2 flex-shrink-0" style="background-color: #1abc9c;">
                                                                                    {{ number_format($sale['sale_amount'], 0, ',', '.') }}€
                                                                                </span>
                                                                            </div>
                                                                            
                                                                            <!-- Buyer and Date -->
                                                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                                                <span class="badge bg-info bg-opacity-10 text-info small">
                                                                                    Comprador: {{ $sale['buyer_name'] }}
                                                                                </span>
                                                                                <small class="text-muted">
                                                                                    <i class="fas fa-calendar-alt me-1"></i>{{ $sale['sale_date']->format('d/m/Y') }}
                                                                                </small>
                                                                            </div>
                                                                            
                                                                            <!-- Market Value and Profit -->
                                                                            <div class="d-flex justify-content-between align-items-center">
                                                                                @if($sale['market_value'] !== null)
                                                                                    <small class="text-muted">
                                                                                        <i class="fas fa-chart-line me-1"></i>
                                                                                        VM: {{ number_format($sale['market_value'], 0, ',', '.') }}€
                                                                                    </small>
                                                                                    <span class="badge {{ $sale['profit_amount'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                                                        {{ $sale['profit_amount'] >= 0 ? '+' : '' }}{{ number_format($sale['profit_amount'], 0, ',', '.') }}€ 
                                                                                        ({{ $sale['profit_amount'] >= 0 ? '+' : '' }}{{ number_format($sale['profit_percentage'], 1) }}%)
                                                                                    </span>
                                                                                @else
                                                                                    <small class="text-muted">
                                                                                        <i class="fas fa-chart-line me-1"></i>VM: No disponible
                                                                                    </small>
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        @else
                                                            <div class="text-center py-4">
                                                                <i class="fas fa-shopping-bag fa-2x text-muted mb-2"></i>
                                                                <p class="text-muted mb-0">No hay ventas disponibles</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay datos de ventas disponibles</h5>
                                    <p class="text-muted">Los análisis aparecerán cuando haya ventas entre usuarios</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile version without card wrapper -->
        <div class="d-lg-none">
            <!-- Mobile tabs navigation -->
            <div class="mobile-tabs-nav mb-4">
                <ul class="nav nav-pills d-flex" id="mobileLeagueTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="mobile-classification-tab" data-bs-toggle="tab" data-bs-target="#mobile-classification" type="button" role="tab" aria-controls="mobile-classification" aria-selected="true">
                            <i class="fas fa-list-ol me-1"></i>
                            <span class="d-none d-sm-inline">Clasificación</span>
                            <span class="d-sm-none">Ranking</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-transactions-tab" data-bs-toggle="tab" data-bs-target="#mobile-transactions" type="button" role="tab" aria-controls="mobile-transactions" aria-selected="false">
                            <i class="fas fa-exchange-alt me-1"></i>
                            <span class="d-none d-sm-inline">Transacciones</span>
                            <span class="d-sm-none">Trans.</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-balance-chart-tab" data-bs-toggle="tab" data-bs-target="#mobile-balance-chart" type="button" role="tab" aria-controls="mobile-balance-chart" aria-selected="false">
                            <i class="fas fa-chart-line me-1"></i>
                            <span class="d-none d-sm-inline">Gráfica</span>
                            <span class="d-sm-none">Graf.</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-transfers-tab" data-bs-toggle="tab" data-bs-target="#mobile-transfers" type="button" role="tab" aria-controls="mobile-transfers" aria-selected="false">
                            <i class="fas fa-handshake me-1"></i>
                            <span class="d-none d-sm-inline">Traspasos</span>
                            <span class="d-sm-none">Trasp.</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-transfer-profits-tab" data-bs-toggle="tab" data-bs-target="#mobile-transfer-profits" type="button" role="tab" aria-controls="mobile-transfer-profits" aria-selected="false">
                            <i class="fas fa-coins me-1"></i>
                            <span class="d-none d-sm-inline">Ganancias</span>
                            <span class="d-sm-none">Gan.</span>
                        </button>
                    </li>
                    @if(Auth::user()->isFullAdministrator())
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-purchase-analysis-tab" data-bs-toggle="tab" data-bs-target="#mobile-purchase-analysis" type="button" role="tab" aria-controls="mobile-purchase-analysis" aria-selected="false">
                            <i class="fas fa-chart-bar me-1"></i>
                            <span class="d-none d-sm-inline">Análisis</span>
                            <span class="d-sm-none">Anál.</span>
                        </button>
                    </li>
                    @endif
                </ul>
            </div>

            <!-- Mobile tab content -->
            <div class="tab-content" id="mobileLeagueTabsContent">
                <div class="tab-pane fade show active" id="mobile-classification" role="tabpanel" aria-labelledby="mobile-classification-tab">
                    @if($league->biwengerUsers->count() > 0)
                        <div id="classificationMobile">
                            @foreach($league->biwengerUsers->filter(function($user) { return $user->position > 0 && $user->points > 0; }) as $user)
                                @php
                                    $currentBalance = $user->balances->sortByDesc('created_at')->first();
                                    $hasNegativeCash = $currentBalance && $currentBalance->cash < 0;
                                @endphp
                                <div class="card mb-3 ranking-card {{ $hasNegativeCash ? 'ranking-card-negative' : '' }}">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="ranking-position me-3">
                                                @if($user->position <= 3)
                                                    <i class="fas fa-medal medal-{{ $user->position }} fa-2x"></i>
                                                @else
                                                    <div class="position-badge">{{ $user->position }}</div>
                                                @endif
                                            </div>
                                            <div class="player-info flex-grow-1">
                                                <div class="d-flex align-items-center">
                                                    <div class="player-avatar-mobile me-2">
                                                        @if($user->icon)
                                                            <img src="{{ $user->icon }}" alt="{{ $user->name }}" class="rounded-circle" width="35" height="35" style="object-fit: cover;">
                                                        @else
                                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 player-name">{{ $user->name }}</h6>
                                                        <small class="text-muted">ID: {{ $user->biwenger_id }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="points-display text-end">
                                                <div class="points-value">{{ number_format($user->points, 0, ',', '.') }}</div>
                                                <small class="text-muted">puntos</small>
                                            </div>
                                        </div>
                                        
                                        <div class="stats-grid">
                                            <div class="stat-item">
                                                <div class="stat-label">Valor Equipo</div>
                                                <div class="stat-value">
                                                    @if($currentBalance)
                                                        {{ number_format($currentBalance->team_value, 0, ',', '.') }}€
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-label">Dinero</div>
                                                <div class="stat-value {{ $hasNegativeCash ? 'text-danger' : '' }}">
                                                    @if($currentBalance)
                                                        {{ number_format($currentBalance->cash, 0, ',', '.') }}€
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-label">Puja Máxima</div>
                                                <div class="stat-value">
                                                    @if($currentBalance)
                                                        {{ number_format($currentBalance->maximum_bid, 0, ',', '.') }}€
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="stat-item">
                                                <div class="stat-label">Balance Total</div>
                                                <div class="stat-value fw-bold">
                                                    @if($currentBalance)
                                                        {{ number_format($currentBalance->balance, 0, ',', '.') }}€
                                                    @else
                                                        -
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay participantes en esta liga</h5>
                            <p class="text-muted">Los participantes aparecerán aquí cuando se sincronicen los datos</p>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="mobile-transactions" role="tabpanel" aria-labelledby="mobile-transactions-tab">
                    <div id="transactionsMobile">
                        <!-- Cards will be loaded via AJAX -->
                    </div>
                </div>

                <div class="tab-pane fade" id="mobile-transfers" role="tabpanel" aria-labelledby="mobile-transfers-tab">
                    @php
                        $userTransfers = $league->getUserTransfers();
                    @endphp
                    
                    @if($userTransfers->count() > 0)
                        <div class="mb-4">
                            <div class="alert text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); border: none;">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Total de traspasos entre usuarios:</strong> {{ $league->getTotalUserTransfers() }}
                            </div>
                        </div>
                        
                        @foreach($userTransfers as $transferData)
                            <div class="card mb-4">
                                <div class="card-header text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);">
                                    <h6 class="mb-0">
                                        <i class="fas fa-handshake me-2"></i>{{ $transferData['users_pair'] }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h4 class="mb-0" style="color: #3498db;">{{ $transferData['total_transfers'] }}</h4>
                                        <small class="text-muted">{{ $transferData['total_transfers'] == 1 ? 'traspaso' : 'traspasos' }}</small>
                                    </div>
                                    
                                    @if($transferData['transfers']->count() <= 5)
                                        <div class="transfers-list">
                                            @foreach($transferData['transfers'] as $transfer)
                                                @php
                                                    $playerPrice = $transfer->getFormattedPlayerPrice();
                                                @endphp
                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                            <small class="text-muted">
                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                            </small>
                                                            @if($playerPrice)
                                                                <br><small class="text-info">
                                                                    <i class="fas fa-chart-line me-1"></i>Precio: {{ $playerPrice['formatted_price'] }}
                                                                    @if($playerPrice['price_increment'] != 0)
                                                                        <span class="text-{{ $playerPrice['price_increment'] >= 0 ? 'success' : 'danger' }}">
                                                                            ({{ $playerPrice['formatted_increment'] }})
                                                                        </span>
                                                                    @endif
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-success">{{ number_format($transfer->amount, 0, ',', '.') }}€</span><br>
                                                            <small class="text-muted">{{ $transfer->date->format('d/m/Y') }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center">
                                            <p class="text-muted mb-2">Últimos 3 traspasos:</p>
                                            @foreach($transferData['transfers']->take(3) as $transfer)
                                                @php
                                                    $playerPrice = $transfer->getFormattedPlayerPrice();
                                                @endphp
                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                            <small class="text-muted">
                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                            </small>
                                                            @if($playerPrice)
                                                                <br><small class="text-info">
                                                                    <i class="fas fa-chart-line me-1"></i>Precio: {{ $playerPrice['formatted_price'] }}
                                                                    @if($playerPrice['price_increment'] != 0)
                                                                        <span class="text-{{ $playerPrice['price_increment'] >= 0 ? 'success' : 'danger' }}">
                                                                            ({{ $playerPrice['formatted_increment'] }})
                                                                        </span>
                                                                    @endif
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="badge bg-success">{{ number_format($transfer->amount, 0, ',', '.') }}€</span><br>
                                                            <small class="text-muted">{{ $transfer->date->format('d/m/Y') }}</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                            @if($transferData['transfers']->count() > 3)
                                                <small class="text-muted">y {{ $transferData['transfers']->count() - 3 }} más...</small>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay traspasos entre usuarios</h5>
                            <p class="text-muted">Los traspasos entre usuarios aparecerán aquí cuando se realicen transacciones</p>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="mobile-balance-chart" role="tabpanel" aria-labelledby="mobile-balance-chart-tab">
                    <div class="p-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">
                                <i class="fas fa-chart-line me-2 text-primary"></i>
                                Evolución del Balance
                            </h6>
                            <div class="chart-controls">
                                <button class="btn btn-sm btn-primary" id="toggleLegendMobile">
                                    <i class="fas fa-eye-slash me-1"></i>Ocultar Leyenda
                                </button>
                            </div>
                        </div>
                        <small class="text-muted d-block mb-3">
                            Historial de balance (dinero + valor del equipo) desde el inicio de la liga
                        </small>
                        
                        <div class="chart-container-mobile">
                            <canvas id="balanceChartMobile" style="display: block;"></canvas>
                            <div id="chartErrorMobile" class="text-center py-5" style="display: none;">
                                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-3"></i>
                                <h6 class="text-muted">No hay datos suficientes</h6>
                                <p class="text-muted">La gráfica aparecerá cuando haya datos disponibles</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(Auth::user()->isFullAdministrator())
                <div class="tab-pane fade" id="mobile-purchase-analysis" role="tabpanel" aria-labelledby="mobile-purchase-analysis-tab">
                    <div class="p-3">
                        @php
                            $purchaseAnalysis = $league->getPurchaseAnalysis();
                        @endphp
                        
                        @if($purchaseAnalysis->count() > 0)
                            <div class="mb-3">
                                <div class="alert text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); border: none;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Análisis de Compras:</strong> % promedio de sobrepago vs valor de mercado. Solo se muestran compras con datos de mercado.
                                </div>
                            </div>
                            
                            @foreach($purchaseAnalysis as $index => $analysis)
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-secondary me-2">{{ $index + 1 }}</span>
                                                <div>
                                                    <h6 class="mb-0">{{ $analysis['user_name'] }}</h6>
                                                    <small class="text-muted">{{ $analysis['total_purchases'] }} fichajes</small>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge {{ $analysis['average_overpay_percentage'] >= 0 ? 'bg-danger' : 'bg-success' }} fs-6">
                                                    {{ $analysis['average_overpay_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_overpay_percentage'], 1) }}%
                                                </span>
                                            </div>
                                        </div>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <small class="text-muted d-block">Pagado</small>
                                                <strong>{{ number_format($analysis['total_amount_paid'], 0, ',', '.') }}€</strong>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Mercado</small>
                                                <span class="text-muted">{{ number_format($analysis['total_market_value'], 0, ',', '.') }}€</span>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted d-block">Diferencia</small>
                                                <span class="fw-bold {{ $analysis['total_overpay_amount'] >= 0 ? 'text-danger' : 'text-success' }}">
                                                    {{ $analysis['total_overpay_amount'] >= 0 ? '+' : '' }}{{ number_format($analysis['total_overpay_amount'], 0, ',', '.') }}€
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No hay datos disponibles</h6>
                                <p class="text-muted">Los análisis aparecerán cuando haya transacciones con datos históricos</p>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

                <div class="tab-pane fade" id="mobile-transfer-profits" role="tabpanel" aria-labelledby="mobile-transfer-profits-tab">
                    <div class="p-3">
                        @php
                            $transferProfitAnalysis = $league->getTransferProfitAnalysis();
                        @endphp
                        
                        @if($transferProfitAnalysis->count() > 0)
                            <div class="mb-3">
                                <div class="alert text-white" style="background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); border: none;">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Ganancias por Ventas:</strong> Beneficios comparando precio de venta vs valor de mercado del día.
                                </div>
                            </div>
                            
                            <!-- Transfers Grid for Mobile -->
                            @foreach($transferProfitAnalysis as $analysis)
                                @php
                                    // Order sales by absolute profit amount (highest impact first)
                                    $sortedSales = collect($analysis['sales'])->sortByDesc(function($sale) {
                                        return $sale['profit_amount'] !== null ? abs($sale['profit_amount']) : 0;
                                    });
                                    $totalSales = $sortedSales->count();
                                @endphp
                                <div class="card border-0 shadow-sm mb-3">
                                    <!-- Collapsible Header -->
                                    <div class="card-header border-0 text-white collapsed" 
                                         style="cursor: pointer; background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);" 
                                         data-bs-toggle="collapse" 
                                         data-bs-target="#mobile-sales-{{ $analysis['user']->biwenger_id }}" 
                                         aria-expanded="false">
                                        <div class="d-flex justify-content-between align-items-center py-2">
                                            <div class="d-flex align-items-center flex-grow-1">
                                                <i class="fas fa-coins fa-lg me-3"></i>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">{{ $analysis['user_name'] }}</h6>
                                                    <small class="opacity-75">{{ $totalSales }} {{ $totalSales == 1 ? 'venta' : 'ventas' }} • {{ number_format($analysis['total_revenue'], 0, ',', '.') }}€</small>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-light text-dark me-2">
                                                    {{ $analysis['average_profit_percentage'] >= 0 ? '+' : '' }}{{ number_format($analysis['average_profit_percentage'], 1) }}%
                                                </span>
                                                <i class="fas fa-chevron-down transition-rotate"></i>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Collapsible Body with Sales -->
                                    <div id="mobile-sales-{{ $analysis['user']->biwenger_id }}" class="collapse">
                                        <div class="card-body p-0">
                                            @if($sortedSales->count() > 0)
                                                @foreach($sortedSales as $sale)
                                                    @php
                                                        $playerImageUrl = $sale['player_id'] 
                                                            ? 'https://cdn.biwenger.com/cdn-cgi/image/f=avif/i/p/' . $sale['player_id'] . '.png'
                                                            : '';
                                                        $fallbackImage = 'https://via.placeholder.com/50x50/6c757d/ffffff?text=' . urlencode(strtoupper(substr($sale['player_name'] ?? 'P', 0, 2)));
                                                    @endphp
                                                    <div class="transfer-row p-3 border-bottom">
                                                        <div class="d-flex align-items-start gap-3">
                                                            <!-- Player Image -->
                                                            @if($sale['player_id'])
                                                                <div class="flex-shrink-0">
                                                                    <img src="{{ $playerImageUrl }}" 
                                                                         alt="{{ $sale['player_name'] }}" 
                                                                         class="rounded-circle border border-2 border-light shadow-sm" 
                                                                         width="50" 
                                                                         height="50" 
                                                                         style="object-fit: cover;"
                                                                         onerror="this.src='{{ $fallbackImage }}'">
                                                                </div>
                                                            @endif
                                                            
                                                            <!-- Sale Details -->
                                                            <div class="flex-grow-1 min-w-0">
                                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                                    <h6 class="mb-0 fw-bold text-truncate">
                                                                        {{ $sale['player_name'] }}
                                                                    </h6>
                                                                    <span class="badge text-white ms-2 flex-shrink-0" style="background-color: #1abc9c;">
                                                                        {{ number_format($sale['sale_amount'], 0, ',', '.') }}€
                                                                    </span>
                                                                </div>
                                                                
                                                                <!-- Buyer and Date -->
                                                                <div class="mb-1">
                                                                    <span class="badge bg-info bg-opacity-10 text-info small">
                                                                        Comprador: {{ $sale['buyer_name'] }}
                                                                    </span>
                                                                </div>
                                                                <div class="mb-1">
                                                                    <small class="text-muted">
                                                                        <i class="fas fa-calendar-alt me-1"></i>{{ $sale['sale_date']->format('d/m/Y') }}
                                                                    </small>
                                                                </div>
                                                                
                                                                <!-- Market Value and Profit -->
                                                                <div class="d-flex justify-content-between align-items-center">
                                                                    @if($sale['market_value'] !== null)
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-chart-line me-1"></i>
                                                                            VM: {{ number_format($sale['market_value'], 0, ',', '.') }}€
                                                                        </small>
                                                                        <span class="badge {{ $sale['profit_amount'] >= 0 ? 'bg-success' : 'bg-danger' }}">
                                                                            {{ $sale['profit_amount'] >= 0 ? '+' : '' }}{{ number_format($sale['profit_amount'], 0, ',', '.') }}€
                                                                        </span>
                                                                    @else
                                                                        <small class="text-muted">
                                                                            <i class="fas fa-chart-line me-1"></i>VM: No disponible
                                                                        </small>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @else
                                                <div class="text-center py-4">
                                                    <i class="fas fa-shopping-bag fa-2x text-muted mb-2"></i>
                                                    <p class="text-muted mb-0">No hay ventas disponibles</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No hay datos disponibles</h6>
                                <p class="text-muted">Los análisis aparecerán cuando haya ventas entre usuarios</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('css/leagues.css') }}">
@endpush

@push('scripts')
<script src="{{ asset('js/league-ranking.js') }}"></script>
<script src="{{ asset('js/league-transactions.js') }}"></script>
<script src="{{ asset('js/league-balance-chart.js') }}"></script>
<script>
// Initialize DataTable for Purchase Analysis
$(document).ready(function() {
    if ($('#purchaseAnalysisTable').length) {
        $('#purchaseAnalysisTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "pageLength": 15,
            "order": [[ 6, "desc" ]],  // Order by average overpay percentage descending (column 6)
            "columnDefs": [
                { 
                    "orderable": false, 
                    "targets": [0] // Disable sorting for position column
                },
                {
                    // Use data-sort attribute for numeric columns
                    "orderDataType": "dom-data-sort",
                    "type": "num",
                    "targets": [2, 3, 4, 5, 6] // Total Fichajes, Total Pagado, Valor Mercado, Sobrepago, %
                }
            ],
            "responsive": true,
            "autoWidth": false,
            "searching": true,
            "paging": false,
            "info": false
        });
    }

    // Initialize DataTable for Transfer Profits Analysis
    if ($('#transferProfitsTable').length) {
        $('#transferProfitsTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "pageLength": 15,
            "order": [[ 6, "desc" ]],  // Order by average profit percentage descending (column 6)
            "columnDefs": [
                {
                    // Use data-sort attribute for numeric columns
                    "orderDataType": "dom-data-sort",
                    "type": "num",
                    "targets": [1, 2, 3, 4, 5, 6] // Total Ventas, Con Análisis, Ingresos, Valor Mercado, Ganancias, %
                }
            ],
            "responsive": true,
            "autoWidth": false,
            "searching": true,
            "paging": false,
            "info": false
        });
    }

    // Initialize DataTables for Individual User Purchase Tables
    if ($('.user-purchases-table').length) {
        $('.user-purchases-table').each(function() {
            $(this).DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                },
                "pageLength": 15,
                "order": [[ 6, "desc" ]],  // Order by difference amount descending (column 6, now moved one position)
                "columnDefs": [
                    { 
                        "orderable": false, 
                        "targets": [0] // Disable sorting for position column
                    },
                    {
                        // Use data-sort attribute for numeric columns
                        "orderDataType": "dom-data-sort",
                        "type": "num",
                        "targets": [2, 3, 4, 5, 6, 7] // Fecha, Precio Pagado, Valor Mercado, Tendencia, Diferencia, %
                    }
                ],
                "responsive": true,
                "autoWidth": false,
                "searching": true,
                "paging": false, // Disable pagination for cleaner view
                "info": false,
                "dom": 'frt' // Only show filter and table
            });
        });
    }

    // DataTables plugin to support data-sort attribute
    $.fn.dataTable.ext.order['dom-data-sort'] = function(settings, col) {
        return this.api().column(col, {order:'index'}).nodes().map(function(td, i) {
            var sortValue = $(td).attr('data-sort');
            return sortValue ? parseFloat(sortValue) : 0;
        });
    };
});
</script>
@endpush
