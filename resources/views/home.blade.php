@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4>Bienvenido {{ Auth::user()->name }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-trophy fa-2x mb-2"></i>
                                    <h3>{{ $stats['total_leagues'] }}</h3>
                                    <p class="mb-0">Liga{{ $stats['total_leagues'] != 1 ? 's' : '' }} Accesible{{ $stats['total_leagues'] != 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <h3>{{ $stats['total_users'] }}</h3>
                                    <p class="mb-0">Usuario{{ $stats['total_users'] != 1 ? 's' : '' }} Total{{ $stats['total_users'] != 1 ? 'es' : '' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-exchange-alt fa-2x mb-2"></i>
                                    <h3>{{ number_format($stats['total_transactions']) }}</h3>
                                    <p class="mb-0">Transaccione{{ $stats['total_transactions'] != 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3 mb-md-0">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <h3>{{ $stats['recent_transactions']->count() }}</h3>
                                    <p class="mb-0">Reciente{{ $stats['recent_transactions']->count() != 1 ? 's' : '' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-trophy fa-2x text-primary mb-2"></i>
                                    <h5>Mis Ligas</h5>
                                    <p class="text-muted">Ver las ligas a las que tienes acceso</p>
                                    <a href="{{ route('leagues.index') }}" class="btn btn-primary">
                                        <i class="fas fa-eye me-1"></i>
                                        Ver Ligas
                                    </a>
                                </div>
                            </div>
                        </div>
                        @if(Auth::user()->canManageAccess())
                        <div class="col-md-6 mb-3 mb-md-0">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <i class="fas fa-users-cog fa-2x text-warning mb-2"></i>
                                    <h5>Gestionar Accesos</h5>
                                    <p class="text-muted">Administrar usuarios y permisos</p>
                                    <a href="{{ route('user-leagues.manage') }}" class="btn btn-warning">
                                        <i class="fas fa-cog me-1"></i>
                                        Gestionar
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4 mb-4">
        <div class="col-md-8 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-list me-1"></i>
                        Resumen de Ligas
                    </h6>
                </div>
                <div class="card-body">
                    @if($stats['leagues_summary']->isEmpty())
                        <div class="text-center py-3">
                            <i class="fas fa-trophy fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No tienes acceso a ninguna liga</p>
                        </div>
                    @else
                        <div class="row">
                            @foreach($stats['leagues_summary'] as $league)
                                <div class="col-md-6 mb-3">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded">
                                        <div>
                                            <h6 class="mb-0">{{ $league['name'] }}</h6>
                                            <small class="text-muted">{{ $league['users_count'] }} usuarios</small>
                                        </div>
                                        <a href="{{ route('leagues.show', $league['id']) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4 mb-md-0">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-clock me-1"></i>
                        Transacciones Recientes
                    </h6>
                </div>
                <div class="card-body">
                    @if($stats['recent_transactions']->isEmpty())
                        <div class="text-center py-3">
                            <i class="fas fa-exchange-alt fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No hay transacciones recientes</p>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach($stats['recent_transactions'] as $transaction)
                                <div class="list-group-item px-0 py-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1 small">{{ $transaction->player_name ?? 'Sin descripción' }}</h6>
                                            <p class="mb-1 small text-muted">
                                                @if($transaction->userFrom)
                                                    <span class="text-danger">{{ $transaction->userFrom->name }}</span>
                                                @else
                                                    <span class="text-muted">Mercado</span>
                                                @endif
                                                →
                                                @if($transaction->userTo)
                                                    <span class="text-success">{{ $transaction->userTo->name }}</span>
                                                @else
                                                    <span class="text-muted">Mercado</span>
                                                @endif
                                            </p>
                                            <small class="text-muted">{{ $transaction->date->diffForHumans() }}</small>
                                        </div>
                                        @if($transaction->amount)
                                            <span class="badge bg-secondary">{{ number_format($transaction->amount) }}€</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
