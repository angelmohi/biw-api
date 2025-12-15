@extends('layouts.app')

@section('content')

<div class="card mb-4">
    <div class="card-body m-2">
        <div class="d-flex justify-content-between">
            <div>
                <h4 class="card-title align-middle d-inline pt-2">Ligas</h4>
            </div>
            @if(Auth::user()->isFullAdministrator())
            <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with buttons">
                <a class="btn btn-outline-primary" type="button" href="{{ route('leagues.create') }}">
                    <i class="fas fa-plus me-2"></i> Crear liga
                </a>
            </div>
            @endif
        </div>
        <hr>
        
        @if($leagues->count() > 0)
            <div class="row py-3">
                @foreach ($leagues as $league)
                    <div class="col-md-4 mb-4">
                        <a href="{{ route('leagues.show', $league->id) }}" class="text-decoration-none">
                            <div class="card h-100 shadow-sm border-0" style="transition: transform 0.2s;">
                                <div class="card-header text-white text-center py-3 league-header">
                                    <h5 class="card-title mb-0 fw-bold">
                                        <i class="fas fa-trophy me-2"></i>{{ $league->name }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    
                                    @if($league->biwengerUsers->count() > 0)
                                        <h6 class="text-muted mb-2">Top 3 Clasificación:</h6>
                                        <div class="ranking">
                                            @foreach($league->biwengerUsers->filter(function($user) { return $user->position > 0 && $user->points > 0; })->sortBy('position')->take(3) as $index => $user)
                                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 rounded" style="background-color: #f8f9fa;">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-medal me-2 medal-{{ $user->position }}" style="font-size: 1.1em;"></i>
                                                        <div class="user-avatar me-2" style="width: 32px; height: 32px; border-radius: 50%; background: white; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #dee2e6;">
                                                            @if($user->icon)
                                                                <img src="{{ $user->icon }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover;">
                                                            @else
                                                                <i class="fas fa-user text-muted" style="font-size: 14px;"></i>
                                                            @endif
                                                        </div>
                                                        <span class="text-dark">{{ $user->name }}</span>
                                                    </div>
                                                    <span class="fw-bold text-success">{{ number_format($user->points) }} pts</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted">No hay usuarios en esta liga</p>
                                    @endif
                                    
                                    <div class="mt-3 pt-2 border-top">                        <small class="text-muted">
                            <i class="fas fa-calendar me-1"></i>
                            Creada: {{ $league->created_at->format('d/m/Y') }}
                        </small>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-5">
                <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                @if(Auth::user()->isFullAdministrator())
                    <h5 class="text-muted">No hay ligas creadas</h5>
                    <p class="text-muted">Crea tu primera liga para comenzar</p>
                    <a href="{{ route('leagues.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Crear Liga
                    </a>
                @else
                    <h5 class="text-muted">No tienes acceso a ninguna liga</h5>
                    <p class="text-muted">Contacta con el administrador para que te asigne acceso a las ligas.</p>
                @endif
            </div>
        @endif
    </div>
</div>

@endsection

@push('css')
<link rel="stylesheet" href="{{ asset('css/leagues.css') }}">
@endpush
