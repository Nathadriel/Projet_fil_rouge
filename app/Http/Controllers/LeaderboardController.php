<?php

namespace App\Http\Controllers;

use App\Models\Transaction;

class LeaderboardController extends Controller
{
    public function index()
    {
        $start = now()->startOfWeek();

        // Top 10 of the BetCoins won since Monday
        $rows = Transaction::select('user_id')
            ->selectRaw('SUM(amount) as total')
            ->where('type', 'win')
            ->where('created_at', '>=', $start)
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user:id,name')
            ->limit(10)
            ->get();

        return view('leaderboard', compact('rows', 'start'));
    }
}
