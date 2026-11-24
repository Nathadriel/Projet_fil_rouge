@extends('layouts.app')

@section('title', 'Paiement réussi')

@section('content')
<div class="narrow center">
    <h1>Paiement réussi</h1>
    <p>Merci pour votre achat ! Vos BetCoins seront ajoutés à votre solde dans quelques secondes,
        le temps que Stripe nous confirme le paiement.</p>
    <a href="{{ route('shop.index') }}" class="btn">Retour à la boutique</a>
</div>
@endsection
