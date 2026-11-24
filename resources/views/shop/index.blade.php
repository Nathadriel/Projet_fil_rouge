@extends('layouts.app')

@section('title', 'Boutique de BetCoins')

@section('content')
<div class="section-head">
    <h1>Boutique</h1>
    <span class="test-badge">Mode test Stripe</span>
</div>
<p class="muted">Rechargez votre solde pour continuer à parier. Les BetCoins restent une <strong>monnaie fictive</strong> :
    ils ne peuvent être ni retirés, ni échangés, ni convertis en argent réel.</p>

@if ($limitReached)
    <p class="alert alert-info">Vous avez atteint la limite de {{ config('shop.daily_limit') }} achats sur 24 heures. Revenez demain !</p>
@endif

<div class="grid packs">
    @foreach ($packs as $key => $pack)
        <article @class(['card', 'pack', 'pack-featured' => $key === 'pro'])>
            <h2>{{ $pack['name'] }}</h2>
            <p class="pack-coins"><span class="coin" aria-hidden="true">◈</span> {{ number_format($pack['betcoins'], 0, ',', ' ') }}</p>
            <p class="pack-price">{{ number_format($pack['price'] / 100, 2, ',', ' ') }} €</p>

            <form method="POST" action="{{ route('shop.checkout') }}">
                @csrf
                <input type="hidden" name="pack" value="{{ $key }}">
                <button type="submit" class="btn btn-block" @disabled($limitReached)>Acheter</button>
            </form>
        </article>
    @endforeach
</div>

<div class="card test-help">
    <h2>Tester le paiement</h2>
    <p>Utilisez la carte de test Stripe <code>4242 4242 4242 4242</code>, une date d'expiration future et n'importe quel code CVC.
        Aucun débit réel n'est effectué. Le paiement se fait sur la page sécurisée de Stripe : BetOnMe ne voit jamais vos coordonnées bancaires.</p>
</div>

@if ($purchases->isNotEmpty())
    <section>
        <h2>Mes derniers achats</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Date</th><th>Pack</th><th>BetCoins</th><th>Montant</th><th>Statut</th></tr>
                </thead>
                <tbody>
                    @foreach ($purchases as $purchase)
                        <tr>
                            <td>{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                            <td>{{ $packs[$purchase->pack]['name'] ?? $purchase->pack }}</td>
                            <td>{{ $purchase->betcoins }}</td>
                            <td>{{ number_format($purchase->amount / 100, 2, ',', ' ') }} €</td>
                            <td><span class="status status-purchase-{{ $purchase->status }}">{{ $purchase->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
@endsection
