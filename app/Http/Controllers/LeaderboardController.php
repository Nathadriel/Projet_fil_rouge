<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\View\View;

class LeaderboardController extends Controller
{
    public function __invoke(): View
    {
        $start = now()->startOfWeek();

        // Top 10 des BetCoins gagnés depuis lundi (somme des transactions de type "gain")
        $rows = Transaction::query()
            ->selectRaw('user_id, SUM(amount) as total')
            ->where('type', TransactionType::Win)
            ->where('created_at', '>=', $start)
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user:id,name')
            ->limit(10)
            ->get();

        return view('leaderboard', ['rows' => $rows, 'start' => $start]);
    }
}
