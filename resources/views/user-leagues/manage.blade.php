@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users-cog me-2"></i>
                        Gestionar Accesos a Ligas
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Alerts -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-coreui-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="manageTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="assign-tab" data-coreui-toggle="tab" data-coreui-target="#assign" type="button" role="tab">
                                <i class="fas fa-plus me-1"></i>
                                Asignar Liga
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="manage-tab" data-coreui-toggle="tab" data-coreui-target="#manage" type="button" role="tab">
                                <i class="fas fa-edit me-1"></i>
                                Gestionar Existentes
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content mt-4" id="manageTabsContent">
                        <!-- Assign League Tab -->
                        <div class="tab-pane fade show active" id="assign" role="tabpanel">
                            <form action="{{ route('user-leagues.assign') }}" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="user_id" class="form-label">Usuario</label>
                                            <select class="form-select" id="user_id" name="user_id" required>
                                                <option value="">Seleccionar usuario...</option>
                                                @foreach($users->where('role.name', 'staff') as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="mb-3">
                                            <label for="league_id" class="form-label">Liga</label>
                                            <select class="form-select" id="league_id" name="league_id" required>
                                                <option value="">Seleccionar liga...</option>
                                                @foreach($leagues as $league)
                                                    <option value="{{ $league->id }}">{{ $league->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary d-block w-100">
                                                <i class="fas fa-plus"></i> Asignar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Manage Existing Tab -->
                        <div class="tab-pane fade" id="manage" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Buscar por Usuario</h6>
                                        </div>
                                        <div class="card-body">
                                            <select class="form-select mb-3" id="user-search">
                                                <option value="">Seleccionar usuario...</option>
                                                @foreach($users->where('role.name', 'staff') as $user)
                                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                                @endforeach
                                            </select>
                                            <div id="user-leagues-list"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Buscar por Liga</h6>
                                        </div>
                                        <div class="card-body">
                                            <select class="form-select mb-3" id="league-search">
                                                <option value="">Seleccionar liga...</option>
                                                @foreach($leagues as $league)
                                                    <option value="{{ $league->id }}">{{ $league->name }}</option>
                                                @endforeach
                                            </select>
                                            <div id="league-users-list"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // User search functionality
    $('#user-search').on('change', function() {
        const userId = $(this).val();
        if (userId) {
            loadUserLeagues(userId);
        } else {
            $('#user-leagues-list').html('');
        }
    });

    // League search functionality
    $('#league-search').on('change', function() {
        const leagueId = $(this).val();
        if (leagueId) {
            loadLeagueUsers(leagueId);
        } else {
            $('#league-users-list').html('');
        }
    });

    function loadUserLeagues(userId) {
        $.get(`/user-leagues/user/${userId}/leagues`)
            .done(function(data) {
                let html = '';
                if (data.leagues.length === 0) {
                    html = '<p class="text-muted">Este usuario no tiene acceso a ninguna liga.</p>';
                } else {
                    html = '<div class="list-group">';
                    data.leagues.forEach(function(league) {
                        const statusBadge = league.is_active 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>';
                        
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${league.name}</h6>
                                    <div>
                                        ${statusBadge}
                                    </div>
                                </div>
                                <p class="mb-1">Asignado: ${league.assigned_at}</p>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-${league.is_active ? 'secondary' : 'success'}" onclick="toggleAccess(${userId}, ${league.id})">
                                        <i class="fas fa-${league.is_active ? 'pause' : 'play'}"></i> ${league.is_active ? 'Desactivar' : 'Activar'}
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeAccess(${userId}, ${league.id})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                $('#user-leagues-list').html(html);
            })
            .fail(function() {
                $('#user-leagues-list').html('<p class="text-danger">Error al cargar las ligas del usuario.</p>');
            });
    }

    function loadLeagueUsers(leagueId) {
        $.get(`/user-leagues/league/${leagueId}/users`)
            .done(function(data) {
                let html = '';
                if (data.users.length === 0) {
                    html = '<p class="text-muted">Esta liga no tiene usuarios asignados.</p>';
                } else {
                    html = '<div class="list-group">';
                    data.users.forEach(function(user) {
                        const statusBadge = user.is_active 
                            ? '<span class="badge bg-success">Activo</span>'
                            : '<span class="badge bg-secondary">Inactivo</span>';
                        
                        const roleBadge = `<span class="badge bg-primary">${user.role}</span>`;
                        
                        html += `
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">${user.name}</h6>
                                    <div>
                                        ${roleBadge} ${statusBadge}
                                    </div>
                                </div>
                                <p class="mb-1">${user.email}</p>
                                <small>Asignado: ${user.assigned_at}</small>
                                <div class="btn-group btn-group-sm mt-2" role="group">
                                    <button type="button" class="btn btn-outline-${user.is_active ? 'secondary' : 'success'}" onclick="toggleAccess(${user.id}, ${leagueId})">
                                        <i class="fas fa-${user.is_active ? 'pause' : 'play'}"></i> ${user.is_active ? 'Desactivar' : 'Activar'}
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="removeAccess(${user.id}, ${leagueId})">
                                        <i class="fas fa-trash"></i> Eliminar
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                $('#league-users-list').html(html);
            })
            .fail(function() {
                $('#league-users-list').html('<p class="text-danger">Error al cargar los usuarios de la liga.</p>');
            });
    }

    // Global functions for actions - Remove changeRole function
    window.toggleAccess = function(userId, leagueId) {
        $.ajax({
            url: '/user-leagues/toggle-access',
            method: 'PUT',
            data: {
                user_id: userId,
                league_id: leagueId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Error: No se pudo cambiar el estado del acceso.');
            }
        });
    };

    window.removeAccess = function(userId, leagueId) {
        $.ajax({
            url: '/user-leagues/remove',
            method: 'DELETE',
            data: {
                user_id: userId,
                league_id: leagueId,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                location.reload();
            },
            error: function() {
                alert('Error: No se pudo eliminar el acceso.');
            }
        });
    };
});
</script>
@endpush
