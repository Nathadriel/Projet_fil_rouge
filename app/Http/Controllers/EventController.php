<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, NewsService $newsService)
    {
        $filter = $request->query('filter', 'open');
        $query = Event::withCount('bets');

        if ($filter === 'closed') {
            $query->whereIn('status', [Event::STATUS_RESOLVED, Event::STATUS_CANCELLED])->latest('resolved_at');
        } elseif ($filter === 'all') {
            $query->whereIn('status', [Event::STATUS_OPEN, Event::STATUS_RESOLVED, Event::STATUS_CANCELLED])->latest();
        } else {
            $query->where('status', Event::STATUS_OPEN)->orderBy('closes_at');
        }

        if (array_key_exists($request->query('category'), Event::CATEGORIES)) {
            $query->where('category', $request->query('category'));
        }

        $events = $query->paginate(9)->withQueryString();
        $news = $newsService->headlines($request->query('category'));

        return view('events.index', compact('events', 'filter', 'news'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(NewsService $newsService)
    {
        Gate::authorize('create', Event::class);

        $news = $newsService->headlines();

        return view('events.create', compact('news'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $request->validate([
            'title' => 'required|string|min:10|max:150',
            'description' => 'required|string|min:20|max:2000',
            'category' => 'required|in:'.implode(',', array_keys(Event::CATEGORIES)),
            'closes_at' => 'required|date|after:+1 hour|before:+1 year',
        ]);

        // user_id and status are not fillable: they are set by the code
        $event = new Event($request->only(['title', 'description', 'category', 'closes_at']));
        $event->user_id = Auth::id();
        $event->status = Event::STATUS_PENDING;
        $event->save();

        return redirect()->route('profile.show')->with('success', 'Événement proposé ! Il sera visible dès qu\'un administrateur l\'aura validé.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Event $event)
    {
        Gate::authorize('view', $event);

        $event->loadCount('bets');

        $userBet = null;
        if (Auth::check()) {
            $userBet = $event->bets()->where('user_id', Auth::id())->first();
        }

        $yes = $event->bets()->where('choice', 'yes');
        $no = $event->bets()->where('choice', 'no');
        $stats = [
            'yes' => ['count' => (clone $yes)->count(), 'total' => (clone $yes)->sum('amount')],
            'no' => ['count' => (clone $no)->count(), 'total' => (clone $no)->sum('amount')],
        ];

        return view('events.show', compact('event', 'userBet', 'stats'));
    }
}
