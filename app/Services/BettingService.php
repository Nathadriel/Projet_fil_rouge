<?php

namespace App\Services;

use App\Enums\BetChoice;
use App\Enums\BetStatus;
use App\Enums\EventStatus;
use App\Enums\TransactionType;
use App\Models\Event;
use App\Models\Pari;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Toute la logique qui fait bouger des BetCoins passe par ce service.
 * Chaque opération est faite dans une transaction SQL : soit tout réussit, soit rien n'est écrit.
 */
class BettingService
{
    public function placeBet(User $user, Event $event, BetChoice $choice, int $amount): Pari
    {
        if ($amount < 1) {
            throw new BettingException('La mise doit être d\'au moins 1 BetCoin.');
        }

        return DB::transaction(function () use ($user, $event, $choice, $amount) {
            // Verrou sur la ligne de l'utilisateur : empêche deux paris simultanés
            // de dépenser deux fois le même solde (race condition)
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if (! $event->acceptsBets()) {
                throw new BettingException('Les paris sont fermés pour cet événement.');
            }
            if ($event->user_id === $user->id) {
                throw new BettingException('Vous ne pouvez pas parier sur votre propre événement.');
            }
            if ($event->paris()->where('user_id', $user->id)->exists()) {
                throw new BettingException('Vous avez déjà parié sur cet événement.');
            }
            if ($user->betcoins < $amount) {
                throw new BettingException('Solde de BetCoins insuffisant.');
            }

            $pari = new Pari(['choice' => $choice, 'amount' => $amount]);
            $pari->user()->associate($user);
            $pari->event()->associate($event);
            $pari->odds = $event->oddsFor($choice);
            $pari->status = BetStatus::Pending;
            $pari->save();

            $user->decrement('betcoins', $amount);
            $this->record($user, $pari, TransactionType::Bet, -$amount, "Mise sur « {$event->title} »");

            Log::info('Pari placé', ['user' => $user->id, 'event' => $event->id, 'amount' => $amount, 'choice' => $choice->value]);

            return $pari;
        });
    }

    public function validateEvent(Event $event, float $oddsYes, float $oddsNo): void
    {
        if ($event->status !== EventStatus::Pending) {
            throw new BettingException('Seul un événement en attente peut être validé.');
        }

        $event->odds_yes = $oddsYes;
        $event->odds_no = $oddsNo;
        $event->status = EventStatus::Open;
        $event->save();

        Log::info('Événement validé', ['event' => $event->id]);
    }

    public function refuseEvent(Event $event): void
    {
        if ($event->status !== EventStatus::Pending) {
            throw new BettingException('Seul un événement en attente peut être refusé.');
        }

        $event->status = EventStatus::Refused;
        $event->save();

        Log::info('Événement refusé', ['event' => $event->id]);
    }

    /** Saisit le résultat, puis crédite les gagnants (mise x cote) */
    public function resolveEvent(Event $event, BetChoice $result): void
    {
        DB::transaction(function () use ($event, $result) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($event->status !== EventStatus::Open) {
                throw new BettingException('Seul un événement ouvert peut être résolu.');
            }

            $event->status = EventStatus::Resolved;
            $event->result = $result;
            $event->resolved_at = now();
            $event->save();

            foreach ($event->paris()->with('user')->get() as $pari) {
                if ($pari->choice === $result) {
                    $gain = $pari->potentialGain();
                    $pari->status = BetStatus::Won;
                    $pari->payout = $gain;
                    $pari->user->increment('betcoins', $gain);
                    $this->record($pari->user, $pari, TransactionType::Win, $gain, "Gain sur « {$event->title} »");
                } else {
                    $pari->status = BetStatus::Lost;
                }
                $pari->save();
            }

            Log::info('Événement résolu', ['event' => $event->id, 'result' => $result->value]);
        });
    }

    /** Annule un événement ouvert et rembourse toutes les mises */
    public function cancelEvent(Event $event): void
    {
        DB::transaction(function () use ($event) {
            $event = Event::whereKey($event->id)->lockForUpdate()->firstOrFail();

            if ($event->status !== EventStatus::Open) {
                throw new BettingException('Seul un événement ouvert peut être annulé.');
            }

            $event->status = EventStatus::Cancelled;
            $event->resolved_at = now();
            $event->save();

            foreach ($event->paris()->with('user')->get() as $pari) {
                $pari->status = BetStatus::Refunded;
                $pari->payout = $pari->amount;
                $pari->save();
                $pari->user->increment('betcoins', $pari->amount);
                $this->record($pari->user, $pari, TransactionType::Refund, $pari->amount, "Remboursement « {$event->title} »");
            }

            Log::info('Événement annulé', ['event' => $event->id]);
        });
    }

    public function grantSignupBonus(User $user): void
    {
        // Le solde par défaut (100) est posé par la colonne ; on trace simplement l'opération
        $this->record($user, null, TransactionType::SignupBonus, User::SIGNUP_BONUS, 'Bienvenue sur BetOnMe !');
    }

    private function record(User $user, ?Pari $pari, TransactionType $type, int $amount, string $description): void
    {
        Transaction::create([
            'user_id' => $user->id,
            'pari_id' => $pari?->id,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
        ]);
    }
}
