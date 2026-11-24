<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BettingService
{
    public function placeBet(User $user, Event $event, string $choice, int $amount): Bet
    {
        // SQL transaction: everything is saved or nothing is saved
        return DB::transaction(function () use ($user, $event, $choice, $amount) {
            // Lock the user row so the same balance cannot be spent twice
            $user = User::where('id', $user->id)->lockForUpdate()->first();

            if (! $event->acceptsBets()) {
                throw new BettingException('Les paris sont fermés pour cet événement.');
            }

            if ($event->user_id === $user->id) {
                throw new BettingException('Vous ne pouvez pas parier sur votre propre événement.');
            }

            if ($event->bets()->where('user_id', $user->id)->exists()) {
                throw new BettingException('Vous avez déjà parié sur cet événement.');
            }

            if ($user->betcoins < $amount) {
                throw new BettingException('Solde de BetCoins insuffisant.');
            }

            $bet = new Bet(['choice' => $choice, 'amount' => $amount]);
            $bet->user_id = $user->id;
            $bet->event_id = $event->id;
            $bet->odds = $event->oddsFor($choice);
            $bet->status = 'pending';
            $bet->save();

            $user->decrement('betcoins', $amount);
            $this->addTransaction($user, 'bet', -$amount, 'Mise sur « '.$event->title.' »');

            Log::info('Bet placed', ['user' => $user->id, 'event' => $event->id, 'amount' => $amount]);

            return $bet;
        });
    }

    public function resolveEvent(Event $event, string $result): void
    {
        if ($event->status !== Event::STATUS_OPEN) {
            throw new BettingException('Seul un événement ouvert peut être résolu.');
        }

        DB::transaction(function () use ($event, $result) {
            $event->status = Event::STATUS_RESOLVED;
            $event->result = $result;
            $event->resolved_at = now();
            $event->save();

            foreach ($event->bets()->with('user')->get() as $bet) {
                if ($bet->choice === $result) {
                    $gain = $bet->potentialGain();
                    $bet->status = 'won';
                    $bet->payout = $gain;
                    $bet->user->increment('betcoins', $gain);
                    $this->addTransaction($bet->user, 'win', $gain, 'Gain sur « '.$event->title.' »');
                } else {
                    $bet->status = 'lost';
                }
                $bet->save();
            }
        });

        Log::info('Event resolved', ['event' => $event->id, 'result' => $result]);
    }

    public function cancelEvent(Event $event): void
    {
        if ($event->status !== Event::STATUS_OPEN) {
            throw new BettingException('Seul un événement ouvert peut être annulé.');
        }

        DB::transaction(function () use ($event) {
            $event->status = Event::STATUS_CANCELLED;
            $event->resolved_at = now();
            $event->save();

            // Every bet is refunded
            foreach ($event->bets()->with('user')->get() as $bet) {
                $bet->status = 'refunded';
                $bet->payout = $bet->amount;
                $bet->save();

                $bet->user->increment('betcoins', $bet->amount);
                $this->addTransaction($bet->user, 'refund', $bet->amount, 'Remboursement « '.$event->title.' »');
            }
        });

        Log::info('Event cancelled', ['event' => $event->id]);
    }

    public function addTransaction(User $user, string $type, int $amount, string $description): void
    {
        $transaction = new Transaction;
        $transaction->user_id = $user->id;
        $transaction->type = $type;
        $transaction->amount = $amount;
        $transaction->description = $description;
        $transaction->save();
    }
}
