@extends('layouts.app')

@section('content')

<div class="row mb-5">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header league-header-detail">
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
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4 class="fw-bold">{{ $league->biwengerUsers->count() }}</h4>
                        <p class="text-muted mb-0">Participantes</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-user-plus fa-2x text-success mb-2"></i>
                        <h4 class="fw-bold">{{ $league->getTotalTransfers() }}</h4>
                        <p class="text-muted mb-0">Fichajes realizados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-user-minus fa-2x text-warning mb-2"></i>
                        <h4 class="fw-bold">{{ $league->getTotalSales() }}</h4>
                        <p class="text-muted mb-0">Ventas realizadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card text-center stat-card">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-info mb-2"></i>
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
                        <button class="nav-link" id="user-transfers-tab" data-bs-toggle="tab" data-bs-target="#user-transfers" type="button" role="tab" aria-controls="user-transfers" aria-selected="false">
                            <i class="fas fa-handshake me-2"></i>Traspasos entre usuarios
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="balance-chart-tab" data-bs-toggle="tab" data-bs-target="#balance-chart" type="button" role="tab" aria-controls="balance-chart" aria-selected="false">
                            <i class="fas fa-chart-line me-2"></i>Evolución Balance
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-0">
                <div class="tab-content" id="leagueTabsContent">
                    <div class="tab-pane fade show active" id="classification" role="tabpanel" aria-labelledby="classification-tab">
                        @if($league->biwengerUsers->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="rankingTable">
                                    <thead class="table-light">
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
                                            <th class="sortable" width="120" data-column="cash">
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
                                        @foreach($league->biwengerUsers as $user)
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
                                <thead class="table-light">
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
                        <div class="p-3">
                            @php
                                $userTransfers = $league->getUserTransfers();
                            @endphp
                            
                            @if($userTransfers->count() > 0)
                                <div class="row">
                                    <div class="col-12 mb-4">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Total de traspasos entre usuarios:</strong> {{ $league->getTotalUserTransfers() }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    @foreach($userTransfers as $transferData)
                                        <div class="col-md-6 col-lg-4 mb-4">
                                            <div class="card h-100">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0">
                                                        <i class="fas fa-handshake me-2"></i>{{ $transferData['users_pair'] }}
                                                    </h6>
                                                </div>
                                                <div class="card-body">
                                                    <div class="text-center mb-3">
                                                        <h4 class="text-primary mb-0">{{ $transferData['total_transfers'] }}</h4>
                                                        <small class="text-muted">{{ $transferData['total_transfers'] == 1 ? 'traspaso' : 'traspasos' }}</small>
                                                    </div>
                                                    
                                                    @if($transferData['transfers']->count() <= 5)
                                                        <div class="transfers-list">
                                                            @foreach($transferData['transfers'] as $transfer)
                                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div class="flex-grow-1">
                                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                                            <small class="text-muted">
                                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                                            </small>
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
                                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                                    <div class="d-flex justify-content-between align-items-start">
                                                                        <div class="flex-grow-1">
                                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                                            <small class="text-muted">
                                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                                            </small>
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
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <i class="fas fa-handshake fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No hay traspasos entre usuarios</h5>
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
                                            <i class="fas fa-chart-line me-2 text-primary"></i>
                                            Evolución del Balance Total
                                        </h5>
                                        <div class="chart-controls">
                                            <button class="btn btn-sm btn-primary" id="toggleLegend">
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
                        <button class="nav-link" id="mobile-transfers-tab" data-bs-toggle="tab" data-bs-target="#mobile-transfers" type="button" role="tab" aria-controls="mobile-transfers" aria-selected="false">
                            <i class="fas fa-handshake me-1"></i>
                            <span class="d-none d-sm-inline">Traspasos</span>
                            <span class="d-sm-none">Trasp.</span>
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="mobile-balance-chart-tab" data-bs-toggle="tab" data-bs-target="#mobile-balance-chart" type="button" role="tab" aria-controls="mobile-balance-chart" aria-selected="false">
                            <i class="fas fa-chart-line me-1"></i>
                            <span class="d-none d-sm-inline">Gráfica</span>
                            <span class="d-sm-none">Graf.</span>
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Mobile tab content -->
            <div class="tab-content" id="mobileLeagueTabsContent">
                <div class="tab-pane fade show active" id="mobile-classification" role="tabpanel" aria-labelledby="mobile-classification-tab">
                    @if($league->biwengerUsers->count() > 0)
                        <div id="classificationMobile">
                            @foreach($league->biwengerUsers as $user)
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
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Total de traspasos entre usuarios:</strong> {{ $league->getTotalUserTransfers() }}
                            </div>
                        </div>
                        
                        @foreach($userTransfers as $transferData)
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-handshake me-2"></i>{{ $transferData['users_pair'] }}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h4 class="text-primary mb-0">{{ $transferData['total_transfers'] }}</h4>
                                        <small class="text-muted">{{ $transferData['total_transfers'] == 1 ? 'traspaso' : 'traspasos' }}</small>
                                    </div>
                                    
                                    @if($transferData['transfers']->count() <= 5)
                                        <div class="transfers-list">
                                            @foreach($transferData['transfers'] as $transfer)
                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                            <small class="text-muted">
                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                            </small>
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
                                                <div class="transfer-item mb-2 p-2 bg-light rounded">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <strong>{{ $transfer->player_name ?? 'Jugador' }}</strong><br>
                                                            <small class="text-muted">
                                                                {{ $transfer->userFrom->name }} → {{ $transfer->userTo->name }}
                                                            </small>
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
@endpush
