<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();
        $betCount = $user->bets()->count();
        $nextGrade = $user->nextGrade();

        $progress = 100;
        if ($nextGrade) {
            $progress = min(100, round($betCount / $user::GRADES[$nextGrade] * 100));
        }

        $bets = $user->bets()->with('event')->latest()->paginate(10);
        $events = $user->events()->latest()->get();
        $transactions = $user->transactions()->latest()->limit(10)->get();

        return view('profile.show', compact('user', 'betCount', 'nextGrade', 'progress', 'bets', 'events', 'transactions'));
    }

    // GDPR: the user can download all his data
    public function export(Request $request)
    {
        $user = $request->user();

        $data = [
            'exported_at' => now()->toIso8601String(),
            'account' => $user->only(['name', 'email', 'betcoins', 'created_at']),
            'bets' => $user->bets()->with('event:id,title')->get(),
            'events' => $user->events,
            'transactions' => $user->transactions,
            'purchases' => $user->purchases,
        ];

        return response()->json($data, 200, [
            'Content-Disposition' => 'attachment; filename="mes-donnees-betonme.json"',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    // GDPR: the user can delete his account
    public function destroy(Request $request)
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = $request->user();

        Auth::logout();
        Log::info('Account deleted', ['user' => $user->id]);
        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Votre compte et vos données ont été supprimés.');
    }
}
