<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\BettingException;
use App\Services\BettingService;
use Illuminate\Http\Request;

class BetController extends Controller
{
    public function store(Request $request, Event $event, BettingService $bettingService)
    {
        $request->validate([
            'choice' => 'required|in:yes,no',
            'amount' => 'required|integer|min:1|max:100000',
        ]);

        try {
            $bet = $bettingService->placeBet($request->user(), $event, $request->choice, (int) $request->amount);
        } catch (BettingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('events.show', $event)
            ->with('success', 'Pari enregistré : '.$bet->amount.' BetCoins sur '.$bet->choiceLabel().'. Gain potentiel : '.$bet->potentialGain().' BetCoins.');
    }
}
