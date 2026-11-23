@extends('layouts.app')

@section('title', 'Tous les événements')

@section('content')
<div class="section-head">
    <h1>Événements</h1>
    @can('create-event')
        <a href="{{ route('events.create') }}" class="btn">+ Proposer un événement</a>
    @endcan
</div>

<form method="GET" action="{{ route('events.index') }}" class="filters" role="search" aria-label="Filtrer les événements">
    <fieldset class="segmented">
        <legend class="sr-only">Statut</legend>
        @foreach (['ouverts' => 'Ouverts', 'clotures' => 'Clôturés', 'tous' => 'Tous'] as $value => $label)
            <label @class(['active' => $filter === $value])>
                <input type="radio" name="filtre" value="{{ $value }}" @checked($filter === $value) data-autosubmit> {{ $label }}
            </label>
        @endforeach
    </fieldset>
    <label for="categorie" class="sr-only">Catégorie</label>
    <select name="categorie" id="categorie" data-autosubmit>
        <option value="">Toutes les catégories</option>
        @foreach (\App\Models\Event::CATEGORIES as $key => $label)
            <option value="{{ $key }}" @selected(request('categorie') === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <noscript><button type="submit" class="btn btn-small">Filtrer</button></noscript>
</form>

@if ($events->isEmpty())
    <p class="empty">Aucun événement ne correspond à ces filtres.</p>
@else
    <div class="grid">
        @foreach ($events as $event)
            @include('partials.event-card')
        @endforeach
    </div>
    {{ $events->links() }}
@endif
@endsection
