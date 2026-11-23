@extends('layouts.app')

@section('title', 'Classement de la semaine')

@section('content')
<h1>Classement de la semaine</h1>
<p class="muted">BetCoins gagnés depuis le lundi {{ $start->translatedFormat('d F') }}. Remise à zéro chaque lundi.</p>

@if ($rows->isEmpty())
    <p class="empty">Personne n'a encore gagné cette semaine. La place de n°1 vous attend !</p>
@else
    <div class="table-wrap">
        <table class="leaderboard">
            <caption class="sr-only">Top 10 des joueurs de la semaine</caption>
            <thead><tr><th scope="col">Rang</th><th scope="col">Pseudo</th><th scope="col">BetCoins gagnés</th></tr></thead>
            <tbody>
            @foreach ($rows as $i => $row)
                <tr @class(['me' => auth()->id() === $row->user_id])>
                    <td class="rank">{{ ['🥇', '🥈', '🥉'][$i] ?? $i + 1 }}</td>
                    <td>{{ $row->user?->name ?? 'Compte supprimé' }}</td>
                    <td><strong>{{ number_format($row->total, 0, ',', ' ') }}</strong></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endif
@endsection
