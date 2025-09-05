@extends('layouts.app')

@push('css')
<link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<div class="container-fluid px-4">
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 fw-bold mb-3">¡Bienvenido, {{ Auth::user()->name }}!</h1>
                <p class="lead mb-0">Gestiona tus ligas Biwenger y mantente al día con todos los movimientos</p>
            </div>
            <div class="col-md-4 text-end">
                <i class="fas fa-futbol" style="font-size: 5rem; opacity: 0.3;"></i>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Statistics Section -->
        <div class="col-lg-6 order-2 order-lg-1">
            <div class="row">
                <div class="col-6 col-md-6 col-lg-6">
                    <div class="stat-box" style="--stat-color: #3498db;">
                        <div class="stat-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-number">{{ $stats['total_leagues'] }}</div>
                        <div class="stat-label">Liga{{ $stats['total_leagues'] != 1 ? 's' : '' }} Accesible{{ $stats['total_leagues'] != 1 ? 's' : '' }}</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-6 col-lg-6">
                    <div class="stat-box" style="--stat-color: #27ae60;">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number">{{ $stats['total_users'] }}</div>
                        <div class="stat-label">Usuario{{ $stats['total_users'] != 1 ? 's' : '' }} Total{{ $stats['total_users'] != 1 ? 'es' : '' }}</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-6 col-lg-6">
                    <div class="stat-box" style="--stat-color: #e74c3c;">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-number">{{ number_format($stats['total_transactions']) }}</div>
                        <div class="stat-label">Transaccione{{ $stats['total_transactions'] != 1 ? 's' : '' }}</div>
                    </div>
                </div>
                
                <div class="col-6 col-md-6 col-lg-6">
                    <div class="stat-box" style="--stat-color: #f39c12;">
                        <div class="stat-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number">{{ $stats['top_balance_users']->count() }}</div>
                        <div class="stat-label">Top Balance{{ $stats['top_balance_users']->count() != 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>

            <!-- Call to Action Section -->
            <div class="leagues-cta">
                <h3 class="mb-3">🏆 Mis Ligas</h3>
                <p class="mb-4 d-none d-md-block">Explora y gestiona todas las ligas a las que tienes acceso</p>
                <p class="mb-4 d-md-none">Gestiona tus ligas</p>
                <a href="{{ route('leagues.index') }}" class="btn-leagues">
                    <i class="fas fa-eye me-2"></i>
                    Ver Mis Ligas
                </a>
            </div>
        </div>

        <!-- Top Balance Section -->
        <div class="col-lg-6 order-1 order-lg-2">
            <div class="transactions-section">
                <h2 class="section-title">
                    <i class="fas fa-crown text-warning"></i>
                    <span class="d-none d-md-inline">Top 5 VMs</span>
                    <span class="d-md-none">Top Balance</span>
                </h2>
                
                @if($stats['top_balance_users']->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-trophy" style="color: white;"></i>
                        <h5 style="color: white;">Sin clasificación</h5>
                        <p style="color: rgba(255,255,255,0.8);">No hay datos de balance para hoy</p>
                    </div>
                @else
                    <div class="ranking-feed">
                        @foreach($stats['top_balance_users'] as $index => $user)
                            <div class="ranking-item">
                                <div class="ranking-position">
                                    <div class="position-number position-{{ $index + 1 }}">
                                        @if($index == 0)
                                            <i class="fas fa-crown"></i>
                                        @elseif($index == 1)
                                            <i class="fas fa-medal"></i>
                                        @elseif($index == 2)
                                            <i class="fas fa-medal"></i>
                                        @else
                                            {{ $index + 1 }}
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="user-info">
                                    <div class="user-avatar">
                                        @if($user->icon)
                                            <img src="{{ $user->icon }}" alt="{{ $user->name }}" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="avatar-fallback" style="display: none;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @else
                                            <div class="avatar-fallback">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="user-details">
                                        <h4 class="user-name">{{ $user->name }}</h4>
                                        <div class="league-info">
                                            <i class="fas fa-trophy me-1"></i>
                                            {{ $user->league->name }}
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="balance-info">
                                    <div class="balance-amount">
                                        {{ number_format($user->today_balance->balance ?? 0, 0, ',', '.') }}€
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
