<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\BetResource;
use App\Models\Event;
use App\Services\BettingException;
use App\Services\BettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class BetController extends Controller
{
    #[OA\Get(
        path: '/me/bets',
        summary: 'Mes paris',
        description: 'Retourne les paris de l\'utilisateur connecté, du plus récent au plus ancien (10 par page).',
        tags: ['Bets'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Bet'))]
            )),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function index(Request $request)
    {
        $bets = $request->user()->bets()
            ->select('id', 'event_id', 'choice', 'amount', 'odds', 'status', 'payout', 'created_at')
            ->with('event:id,title')
            ->latest()
            ->paginate(10);

        return BetResource::collection($bets);
    }

    #[OA\Post(
        path: '/events/{id}/bets',
        summary: 'Parier sur un événement',
        description: 'Mise des BetCoins sur OUI ou NON. Un seul pari par événement.',
        tags: ['Bets'],
        security: [['BearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 4))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/BetRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Pari enregistré', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Bet')]
            )),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Événement introuvable', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Pari refusé (solde insuffisant, paris fermés, déjà parié...)', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request, Event $event, BettingService $bettingService)
    {
        $request->validate([
            'choice' => 'required|in:yes,no',
            'amount' => 'required|integer|min:1|max:100000',
        ]);

        try {
            $bet = $bettingService->placeBet($request->user(), $event, $request->choice, (int) $request->amount);
        } catch (BettingException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return (new BetResource($bet->load('event:id,title')))->response()->setStatusCode(Response::HTTP_CREATED);
    }
}
