<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request, BettingService $bettingService): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|min:3|max:30|alpha_dash|unique:users',
            'email' => 'required|string|lowercase|email|max:191|unique:users',
            // CNIL recommendation: at least 12 characters with upper case, lower case, number and symbol
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'consent' => 'accepted',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');
        $bettingService->addTransaction($user, 'signup_bonus', 100, 'Bienvenue sur BetOnMe !');

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('events.index')->with('success', 'Bienvenue '.$user->name.' ! 100 BetCoins vous ont été offerts.');
    }
}
