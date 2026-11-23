@extends('layouts.app')

@section('title', 'Inscription')

@section('content')
<div class="narrow">
    <h1>Créer un compte</h1>
    <p class="muted">100 BetCoins offerts à l'inscription. Aucune donnée bancaire n'est demandée.</p>

    <form method="POST" action="{{ route('register') }}" class="card form">
        @csrf
        <label for="name">Pseudo</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autocomplete="username"
               minlength="3" maxlength="30" @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
        @include('partials.field-error', ['name' => 'name'])

        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
               @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
        @include('partials.field-error', ['name' => 'email'])

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required autocomplete="new-password" minlength="12"
               aria-describedby="password-help @error('password') password-error @enderror"
               @error('password') aria-invalid="true" @enderror>
        <p class="help" id="password-help">12 caractères minimum, avec majuscule, minuscule, chiffre et caractère spécial.</p>
        @include('partials.field-error', ['name' => 'password'])

        <label for="password_confirmation">Confirmer le mot de passe</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">

        <label class="checkbox">
            <input type="checkbox" name="consent" value="1" required @checked(old('consent'))>
            J'ai lu et j'accepte la <a href="{{ route('privacy') }}" target="_blank" rel="noopener">politique de confidentialité</a>.
        </label>
        @include('partials.field-error', ['name' => 'consent'])

        <button type="submit" class="btn btn-block">Créer mon compte</button>
        <p class="center">Déjà inscrit ? <a href="{{ route('login') }}">Se connecter</a></p>
    </form>
</div>
@endsection
