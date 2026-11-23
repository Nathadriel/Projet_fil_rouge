<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->loadCount('paris');
        $grade = $user->grade();
        $next = $grade->next();

        return view('profile.show', [
            'user' => $user,
            'grade' => $grade,
            'nextGrade' => $next,
            'progress' => $next ? min(100, (int) round($user->paris_count / $next->value * 100)) : 100,
            'paris' => $user->paris()->with('event')->latest()->paginate(10),
            'myEvents' => $user->events()->latest()->get(),
            'transactions' => $user->transactions()->latest()->limit(10)->get(),
        ]);
    }

    /** RGPD - droit d'accès et de portabilité : export de toutes les données de l'utilisateur */
    public function export(Request $request): JsonResponse
    {
        $user = $request->user()->load(['paris.event:id,title', 'events', 'transactions']);

        return response()->json([
            'exporte_le' => now()->toIso8601String(),
            'compte' => $user->only(['name', 'email', 'betcoins', 'role', 'created_at']),
            'paris' => $user->paris,
            'evenements_proposes' => $user->events,
            'transactions' => $user->transactions,
        ], 200, [
            'Content-Disposition' => 'attachment; filename="mes-donnees-betonme.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /** RGPD - droit à l'effacement */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [], ['password' => 'mot de passe']);

        $user = $request->user();
        Auth::logout();
        Log::info('Compte supprimé', ['user' => $user->id]);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Votre compte et vos données ont été supprimés.');
    }
}
