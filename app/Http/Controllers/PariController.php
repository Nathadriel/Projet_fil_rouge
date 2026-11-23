<?php

namespace App\Http\Controllers;

use App\Enums\BetChoice;
use App\Models\Event;
use App\Services\BettingException;
use App\Services\BettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PariController extends Controller
{
    public function store(Request $request, Event $event, BettingService $betting): RedirectResponse
    {
        $data = $request->validate([
            'choice' => ['required', Rule::enum(BetChoice::class)],
            'amount' => ['required', 'integer', 'min:1', 'max:100000'],
        ], [], ['choice' => 'choix', 'amount' => 'mise']);

        try {
            $pari = $betting->placeBet($request->user(), $event, BetChoice::from($data['choice']), (int) $data['amount']);
        } catch (BettingException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('events.show', $event)
            ->with('success', "Pari enregistré : {$pari->amount} BetCoins sur {$pari->choice->label()}. Gain potentiel : {$pari->potentialGain()} BetCoins.");
    }
}
