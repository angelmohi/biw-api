<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserLeagueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show form to assign leagues to users (admin only)
     */
    public function manage(): View
    {
        $users = User::orderBy('name')->get();
        $leagues = League::orderBy('name')->get();
        
        return view('user-leagues.manage', compact('users', 'leagues'));
    }

    /**
     * Assign a league to a user
     */
    public function assign(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'league_id' => 'required|exists:league,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            if ($user->isFullAdministrator()) {
                flashDangerMessage('No se puede asignar la liga a un Full Administrator.');
                return jsonIframeRedirection(route('user-leagues.manage'));
            }

            if ($user->hasAccessToLeague($request->league_id)) {
                flashDangerMessage('El usuario ya tiene acceso a esta liga.');
                return jsonIframeRedirection(route('user-leagues.manage'));
            }

            $user->leagues()->attach($request->league_id, [
                'is_active' => true
            ]);

            flashSuccessMessage('Liga asignada correctamente al usuario.');
            return jsonIframeRedirection(route('user-leagues.manage'));
            
        } catch (\Exception $e) {
            flashDangerMessage('Error al asignar la liga: ' . $e->getMessage());
            return jsonIframeRedirection(route('user-leagues.manage'));
        }
    }

    /**
     * Remove a league from a user
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'league_id' => 'required|exists:league,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            if ($user->isFullAdministrator()) {
                flashDangerMessage('No se puede eliminar el acceso de un Full Administrator.');
                return jsonIframeRedirection(route('user-leagues.manage'));
            }

            $user->belongsToMany(League::class, 'user_leagues')
                 ->withPivot('is_active')
                 ->withTimestamps()
                 ->detach($request->league_id);

            flashSuccessMessage('Liga eliminada correctamente del usuario.');
            return jsonIframeRedirection(route('user-leagues.manage'));

        } catch (\Exception $e) {
            flashDangerMessage('Error al eliminar la liga: ' . $e->getMessage());
            return jsonIframeRedirection(route('user-leagues.manage'));
        }
    }

    /**
     * Toggle user's access to a league (activate/deactivate)
     */
    public function toggleAccess(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'league_id' => 'required|exists:league,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);

            if ($user->isFullAdministrator()) {
                flashDangerMessage('Los Full Administrators tienen acceso automático a todas las ligas.');
                return jsonIframeRedirection(route('user-leagues.manage'));
            }

            $userLeague = $user->belongsToMany(League::class, 'user_leagues')
                              ->withPivot('is_active')
                              ->withTimestamps()
                              ->where('league.id', $request->league_id)
                              ->first();
            
            if (!$userLeague) {
                flashDangerMessage('El usuario no está asignado a esta liga.');
                return jsonIframeRedirection(route('user-leagues.manage'));
            }

            $newStatus = !$userLeague->pivot->is_active;
            
            $user->belongsToMany(League::class, 'user_leagues')
                 ->withPivot('is_active')
                 ->withTimestamps()
                 ->updateExistingPivot($request->league_id, [
                     'is_active' => $newStatus
                 ]);

            $message = $newStatus ? 'Acceso activado correctamente.' : 'Acceso desactivado correctamente.';

            flashSuccessMessage($message);
            return jsonIframeRedirection(route('user-leagues.manage'));

        } catch (\Exception $e) {
            flashDangerMessage('Error al cambiar el estado: ' . $e->getMessage());
            return jsonIframeRedirection(route('user-leagues.manage'));
        }
    }

    /**
     * Get user assignments for a specific league (AJAX)
     */
    public function getLeagueUsers(League $league)
    {
        $users = $league->allUsers()->get();
        
        return response()->json([
            'users' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? $user->role->display_name : 'Sin rol',
                    'is_active' => $user->pivot->is_active,
                    'assigned_at' => $user->pivot->created_at->format('d/m/Y H:i')
                ];
            })
        ]);
    }

    /**
     * Get league assignments for a specific user (AJAX)
     */
    public function getUserLeagues(User $user)
    {
        if ($user->isFullAdministrator()) {
            $leagues = League::all();
            return response()->json([
                'leagues' => $leagues->map(function ($league) {
                    return [
                        'id' => $league->id,
                        'name' => $league->name,
                        'role' => 'Full Administrator',
                        'is_active' => true,
                        'assigned_at' => 'Automático'
                    ];
                })
            ]);
        }

        $leagues = $user->allLeagues()->get();
        
        return response()->json([
            'leagues' => $leagues->map(function ($league) {
                return [
                    'id' => $league->id,
                    'name' => $league->name,
                    'role' => 'Staff',
                    'is_active' => $league->pivot->is_active,
                    'assigned_at' => $league->pivot->created_at->format('d/m/Y H:i')
                ];
            })
        ]);
    }
}
