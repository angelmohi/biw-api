<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckLeagueAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Get league from route parameter
        $league = $request->route('league');
        
        if ($league && !$user->hasAccessToLeague($league->id)) {
            abort(403, 'No tienes acceso a esta liga.');
        }
        
        return $next($request);
    }
}
