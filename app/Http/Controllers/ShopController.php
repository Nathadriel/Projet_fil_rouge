<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Services\BettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use UnexpectedValueException;

class ShopController extends Controller
{
    public function index(Request $request)
    {
        $packs = config('shop.packs');
        $purchases = $request->user()->purchases()->latest()->limit(5)->get();
        $limitReached = $this->dailyLimitReached($request->user());

        return view('shop.index', compact('packs', 'purchases', 'limitReached'));
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'pack' => 'required|in:'.implode(',', array_keys(config('shop.packs'))),
        ]);

        $user = $request->user();

        if ($this->dailyLimitReached($user)) {
            return back()->with('error', 'Limite de '.config('shop.daily_limit').' achats par 24 h atteinte. Jouez de façon responsable !');
        }

        // The price comes from the server config, not from the form
        $pack = config('shop.packs.'.$request->pack);

        $purchase = new Purchase([
            'pack' => $request->pack,
            'betcoins' => $pack['betcoins'],
            'amount' => $pack['price'],
        ]);
        $purchase->user_id = $user->id;
        $purchase->status = 'pending';
        $purchase->save();

        try {
            $stripe = new StripeClient(config('services.stripe.secret'));

            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                'customer_email' => $user->email,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => $pack['name'].' - '.$pack['betcoins'].' BetCoins'],
                        'unit_amount' => $pack['price'], // Stripe expects cents
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('shop.success'),
                'cancel_url' => route('shop.index'),
                'metadata' => ['purchase_id' => $purchase->id], // used by the webhook
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Stripe checkout error', ['message' => $e->getMessage()]);

            return back()->with('error', 'Le paiement n\'a pas pu être initialisé.');
        }

        $purchase->stripe_session_id = $session->id;
        $purchase->save();

        return redirect()->away($session->url);
    }

    public function success()
    {
        return view('shop.success');
    }

    // Called by Stripe servers (no CSRF token, the signature is checked instead)
    public function webhook(Request $request, BettingService $bettingService)
    {
        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            Log::warning('Invalid Stripe webhook', ['message' => $e->getMessage()]);

            return response('Signature invalide', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            DB::transaction(function () use ($session, $bettingService) {
                $purchase = Purchase::where('stripe_session_id', $session->id)->lockForUpdate()->first();

                // Stripe can send the same event twice: the BetCoins are only given once
                if (! $purchase || $purchase->status === 'paid') {
                    return;
                }

                // Check that Stripe received the expected amount
                if ($session->payment_status !== 'paid' || $session->amount_total !== $purchase->amount) {
                    Log::error('Stripe payment not valid', ['purchase' => $purchase->id]);

                    return;
                }

                $purchase->status = 'paid';
                $purchase->paid_at = now();
                $purchase->save();

                $purchase->user->increment('betcoins', $purchase->betcoins);
                $bettingService->addTransaction($purchase->user, 'purchase', $purchase->betcoins, 'Achat '.config('shop.packs.'.$purchase->pack.'.name'));
            });
        }

        if ($event->type === 'checkout.session.expired') {
            Purchase::where('stripe_session_id', $event->data->object->id)->where('status', 'pending')->update(['status' => 'expired']);
        }

        return response()->json(['received' => true]);
    }

    private function dailyLimitReached($user): bool
    {
        $count = $user->purchases()->where('status', 'paid')->where('paid_at', '>=', now()->subDay())->count();

        return $count >= config('shop.daily_limit');
    }
}
