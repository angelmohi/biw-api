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
        <div class="card">
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
                                        <th>Descripción</th>
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
@endpush
