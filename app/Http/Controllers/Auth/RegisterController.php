<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, BettingService $betting): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:30', 'alpha_dash', 'unique:users,name'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            // Recommandation CNIL : 12 caractères minimum avec majuscules, minuscules, chiffres et caractères spéciaux
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
            'consent' => ['accepted'],
        ], [], ['name' => 'pseudo', 'consent' => 'politique de confidentialité']);

        $user = DB::transaction(function () use ($data, $betting) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // haché automatiquement (cast "hashed", bcrypt)
            ]);
            $betting->grantSignupBonus($user);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('events.index')
            ->with('success', 'Bienvenue '.$user->name.' ! '.User::SIGNUP_BONUS.' BetCoins vous ont été offerts.');
    }
}
