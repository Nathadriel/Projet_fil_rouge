@extends('layouts.app')

@section('title', 'Tous les événements')

@section('content')
<div class="section-head">
    <h1>Événements</h1>
    @can('create', App\Models\Event::class)
        <a href="{{ route('events.create') }}" class="btn">+ Proposer un événement</a>
    @endcan
</div>

<form method="GET" action="{{ route('events.index') }}" class="filters">
    <fieldset class="segmented">
        <legend class="sr-only">Statut</legend>
        @foreach (['open' => 'Ouverts', 'closed' => 'Clôturés', 'all' => 'Tous'] as $value => $label)
            <label @class(['active' => $filter === $value])>
                <input type="radio" name="filter" value="{{ $value }}" @checked($filter === $value) data-autosubmit> {{ $label }}
            </label>
        @endforeach
    </fieldset>

    <label for="category" class="sr-only">Catégorie</label>
    <select name="category" id="category" data-autosubmit>
        <option value="">Toutes les catégories</option>
        @foreach (App\Models\Event::CATEGORIES as $key => $label)
            <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
        @endforeach
    </select>
</form>

<div class="grid">
    @forelse ($events as $event)
        <x-event-card :event="$event" />
    @empty
        <p class="empty">Aucun événement ne correspond à ces filtres.</p>
    @endforelse
</div>

{{ $events->links('pagination::simple-bootstrap-4') }}

@include('partials.news')
@endsection
