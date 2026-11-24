<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\BettingException;
use App\Services\BettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class AdminEventController extends Controller
{
    public function index()
    {
        Gate::authorize('manage', Event::class);

        $pendingEvents = Event::where('status', Event::STATUS_PENDING)->with('user:id,name')->oldest()->get();
        $openEvents = Event::where('status', Event::STATUS_OPEN)->withCount('bets')->orderBy('closes_at')->get();

        return view('admin.index', compact('pendingEvents', 'openEvents'));
    }

    public function approve(Request $request, Event $event)
    {
        Gate::authorize('manage', Event::class);

        $request->validate([
            'odds_yes' => 'required|numeric|min:1.01|max:50',
            'odds_no' => 'required|numeric|min:1.01|max:50',
        ]);

        if ($event->status !== Event::STATUS_PENDING) {
            return back()->with('error', 'Seul un événement en attente peut être validé.');
        }

        $event->odds_yes = $request->odds_yes;
        $event->odds_no = $request->odds_no;
        $event->status = Event::STATUS_OPEN;
        $event->save();

        Log::info('Event approved', ['event' => $event->id]);

        return back()->with('success', '« '.$event->title.' » est maintenant ouvert aux paris.');
    }

    public function refuse(Event $event)
    {
        Gate::authorize('manage', Event::class);

        if ($event->status !== Event::STATUS_PENDING) {
            return back()->with('error', 'Seul un événement en attente peut être refusé.');
        }

        $event->status = Event::STATUS_REFUSED;
        $event->save();

        return back()->with('success', '« '.$event->title.' » a été refusé.');
    }

    public function resolve(Request $request, Event $event, BettingService $bettingService)
    {
        Gate::authorize('manage', Event::class);

        $request->validate([
            'result' => 'required|in:yes,no',
        ]);

        try {
            $bettingService->resolveEvent($event, $request->result);
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', '« '.$event->title.' » est résolu, les gains ont été distribués.');
    }

    public function cancel(Event $event, BettingService $bettingService)
    {
        Gate::authorize('manage', Event::class);

        try {
            $bettingService->cancelEvent($event);
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', '« '.$event->title.' » est annulé, les mises sont remboursées.');
    }
}
