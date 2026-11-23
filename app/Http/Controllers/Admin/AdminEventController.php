<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BetChoice;
use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\BettingException;
use App\Services\BettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function __construct(private BettingService $betting) {}

    public function index(): View
    {
        return view('admin.index', [
            'pending' => Event::where('status', EventStatus::Pending)->with('creator:id,name')->oldest()->get(),
            'open' => Event::open()->withCount('paris')->orderBy('closes_at')->get(),
        ]);
    }

    public function approve(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'odds_yes' => ['required', 'numeric', 'min:1.01', 'max:50'],
            'odds_no' => ['required', 'numeric', 'min:1.01', 'max:50'],
        ], [], ['odds_yes' => 'cote OUI', 'odds_no' => 'cote NON']);

        return $this->run(fn () => $this->betting->validateEvent($event, (float) $data['odds_yes'], (float) $data['odds_no']),
            "« {$event->title} » est maintenant ouvert aux paris.");
    }

    public function refuse(Event $event): RedirectResponse
    {
        return $this->run(fn () => $this->betting->refuseEvent($event), "« {$event->title} » a été refusé.");
    }

    public function resolve(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate(['result' => ['required', Rule::enum(BetChoice::class)]]);

        return $this->run(fn () => $this->betting->resolveEvent($event, BetChoice::from($data['result'])),
            "« {$event->title} » est résolu, les gains ont été distribués.");
    }

    public function cancel(Event $event): RedirectResponse
    {
        return $this->run(fn () => $this->betting->cancelEvent($event), "« {$event->title} » est annulé, les mises sont remboursées.");
    }

    private function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
