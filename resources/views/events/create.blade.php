@extends('layouts.app')

@section('title', 'Proposer un événement')

@section('content')
<div class="narrow">
    <h1>Proposer un événement</h1>
    <p class="muted">Votre proposition sera relue par un administrateur, qui fixera les cotes avant de l'ouvrir aux paris.
        Restez drôle, jamais blessant : les événements visant une personne privée seront refusés.</p>

    <form method="POST" action="{{ route('events.store') }}" class="card form" novalidate>
        @csrf
        <label for="title">Titre <span aria-hidden="true">*</span></label>
        <input type="text" id="title" name="title" value="{{ old('title') }}" required minlength="10" maxlength="150"
               placeholder="Ex. : Un pigeon sera élu au conseil municipal de Lyon avant 2027"
               @error('title') aria-invalid="true" aria-describedby="title-error" @enderror>
        @include('partials.field-error', ['name' => 'title'])

        <label for="description">Description <span aria-hidden="true">*</span></label>
        <textarea id="description" name="description" rows="5" required minlength="20" maxlength="2000"
                  @error('description') aria-invalid="true" aria-describedby="description-error" @enderror>{{ old('description') }}</textarea>
        @include('partials.field-error', ['name' => 'description'])

        <div class="form-row">
            <div>
                <label for="category">Catégorie <span aria-hidden="true">*</span></label>
                <select id="category" name="category" required>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @include('partials.field-error', ['name' => 'category'])
            </div>
            <div>
                <label for="closes_at">Date de clôture des paris <span aria-hidden="true">*</span></label>
                <input type="datetime-local" id="closes_at" name="closes_at" value="{{ old('closes_at') }}" required
                       min="{{ now()->addHour()->format('Y-m-d\TH:i') }}"
                       @error('closes_at') aria-invalid="true" aria-describedby="closes_at-error" @enderror>
                @include('partials.field-error', ['name' => 'closes_at'])
            </div>
        </div>
        <p class="muted small"><span aria-hidden="true">*</span> Champs obligatoires</p>
        <button type="submit" class="btn">Envoyer la proposition</button>
    </form>
</div>
@endsection
