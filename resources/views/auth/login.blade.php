@extends('layouts.app')

@section('title', 'Connexion')

@section('content')
<div class="narrow">
    <h1>Connexion</h1>

    <form method="POST" action="{{ route('login') }}" class="card form">
        @csrf

        <label for="email">Adresse e-mail</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">
        @include('partials.field-error', ['name' => 'email'])

        <label for="password">Mot de passe</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">

        <label class="checkbox"><input type="checkbox" name="remember" value="1"> Rester connecté</label>

        <button type="submit" class="btn btn-block">Se connecter</button>
        <p class="center">Pas encore de compte ? <a href="{{ route('register') }}">S'inscrire</a></p>
    </form>
</div>
@endsection
