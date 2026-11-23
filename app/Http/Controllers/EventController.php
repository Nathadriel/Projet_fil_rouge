<?php

namespace App\Http\Controllers;

use App\Enums\EventStatus;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(Request $request): View
    {
        $filter = $request->query('filtre', 'ouverts');

        $query = Event::query()->withCount('paris');
        match ($filter) {
            'clotures' => $query->closed()->latest('resolved_at'),
            'tous' => $query->whereIn('status', [EventStatus::Open, EventStatus::Resolved, EventStatus::Cancelled])->latest(),
            default => $query->open()->orderBy('closes_at'),
        };

        if ($request->filled('categorie') && array_key_exists($request->query('categorie'), Event::CATEGORIES)) {
            $query->where('category', $request->query('categorie'));
        }

        return view('events.index', [
            'events' => $query->paginate(9)->withQueryString(),
            'filter' => $filter,
        ]);
    }

    public function show(Request $request, Event $event): View
    {
        // Un événement en attente ou refusé n'est visible que par son créateur et les admins
        if (in_array($event->status, [EventStatus::Pending, EventStatus::Refused], true)) {
            Gate::authorize('view-unpublished-event', $event);
        }

        $event->loadCount('paris');
        $userBet = $request->user()
            ? $event->paris()->where('user_id', $request->user()->id)->first()
            : null;

        $stats = $event->paris()
            ->selectRaw('choice, COUNT(*) as total, SUM(amount) as volume')
            ->groupBy('choice')
            ->get()
            ->keyBy(fn ($row) => $row->choice->value);

        return view('events.show', compact('event', 'userBet', 'stats'));
    }

    public function create(): View
    {
        Gate::authorize('create-event');

        return view('events.create', ['categories' => Event::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create-event');

        $data = $request->validate([
            'title' => ['required', 'string', 'min:10', 'max:150'],
            'description' => ['required', 'string', 'min:20', 'max:2000'],
            'category' => ['required', Rule::in(array_keys(Event::CATEGORIES))],
            'closes_at' => ['required', 'date', 'after:+1 hour', 'before:+1 year'],
        ], [], ['title' => 'titre', 'closes_at' => 'date de clôture', 'category' => 'catégorie']);

        $event = new Event($data);
        $event->creator()->associate($request->user());
        $event->status = EventStatus::Pending;
        $event->save();

        return redirect()->route('profile.show')
            ->with('success', 'Événement proposé ! Il sera visible dès qu\'un administrateur l\'aura validé.');
    }
}
