<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\BiwengerUser;
use App\Models\BiwengerUserBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     */
    public function index() : View
    {
        $user = Auth::user();
        
        // Get user's accessible leagues
        if ($user->isFullAdministrator()) {
            $leagues = League::all();
        } else {
            $leagues = League::whereHas('users', function($query) use ($user) {
                $query->where('users.id', $user->id)
                      ->where('user_leagues.is_active', true);
            })->get();
        }
        
        // Calculate statistics
        $stats = [
            'total_leagues' => $leagues->count(),
            'total_users' => BiwengerUser::whereIn('league_id', $leagues->pluck('id'))->count(),
            'total_transactions' => 0,
            'top_balance_users' => collect(),
            'leagues_summary' => $leagues->map(function ($league) {
                return [
                    'name' => $league->name,
                    'users_count' => $league->biwengerUsers()->count(),
                    'id' => $league->id
                ];
            })
        ];
        
        if ($leagues->isNotEmpty()) {
            // Get transaction count
            $stats['total_transactions'] = Transaction::whereHas('userFrom', function($query) use ($leagues) {
                $query->whereIn('league_id', $leagues->pluck('id'));
            })->orWhereHas('userTo', function($query) use ($leagues) {
                $query->whereIn('league_id', $leagues->pluck('id'));
            })->count();
            
            // Get top 5 users with highest balance (most recent balance for each user)
            $stats['top_balance_users'] = BiwengerUser::whereIn('league_id', $leagues->pluck('id'))
                ->with('league')
                ->get()
                ->map(function($user) {
                    $latestBalance = BiwengerUserBalance::where('user_id', $user->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    if ($latestBalance) {
                        $user->today_balance = $latestBalance;
                        return $user;
                    }
                    return null;
                })
                ->filter()
                ->sortByDesc(function($user) {
                    return $user->today_balance->balance ?? 0;
                })
                ->take(5)
                ->values();
        }
        
        return view('home', compact('stats'));
    }
}
