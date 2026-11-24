<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    #[OA\Post(
        path: '/register',
        summary: 'Inscription',
        description: 'Crée un compte avec 100 BetCoins offerts.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'User created'),
                    new OA\Property(property: 'user', ref: '#/components/schemas/User'),
                ]
            )),
            new OA\Response(response: 422, description: 'Erreur de validation (ex : email déjà utilisé)', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Trop de tentatives', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function register(Request $request, BettingService $bettingService)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:30|alpha_dash|unique:users',
            'email' => 'required|string|lowercase|email|max:191|unique:users',
            // CNIL recommendation
            'password' => ['required', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole('user');
        $bettingService->addTransaction($user, 'signup_bonus', 100, 'Bienvenue sur BetOnMe !');

        return response()->json([
            'message' => 'User created',
            'user' => new UserResource($user),
        ], Response::HTTP_CREATED);
    }

    #[OA\Post(
        path: '/login',
        summary: 'Connexion',
        description: 'Vérifie les identifiants et retourne un token Sanctum (valable 24 h).',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(ref: '#/components/schemas/LoginResponse')),
            new OA\Response(response: 401, description: 'Identifiants invalides', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Erreur de validation', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
            new OA\Response(response: 429, description: 'Trop de tentatives', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            Log::warning('Failed API login', ['email' => $request->email, 'ip' => $request->ip()]);

            return response()->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ], Response::HTTP_OK);
    }

    #[OA\Post(
        path: '/logout',
        summary: 'Déconnexion',
        description: 'Supprime le token utilisé pour la requête.',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Token supprimé'),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    #[OA\Get(
        path: '/me',
        summary: 'Mon profil',
        description: 'Retourne le solde, le grade et la progression de l\'utilisateur connecté.',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/User')]
            )),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function me(Request $request)
    {
        return new UserResource($request->user());
    }
}
