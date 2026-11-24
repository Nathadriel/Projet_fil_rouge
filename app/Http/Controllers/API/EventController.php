<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\EventResource;
use App\Models\Event;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use OpenApi\Attributes as OA;

class EventController extends Controller
{
    #[OA\Get(
        path: '/events',
        summary: 'Lister les événements',
        description: 'Retourne les événements paginés (10 par page). Par défaut : les événements ouverts.',
        tags: ['Events'],
        parameters: [
            new OA\Parameter(name: 'filter', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['open', 'closed', 'all'], example: 'open')),
            new OA\Parameter(name: 'category', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['politique', 'tech', 'sport', 'environnement', 'insolite'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(ref: '#/components/schemas/EventCollection')),
        ]
    )]
    public function index(Request $request)
    {
        $filter = $request->query('filter', 'open');

        $query = Event::select('id', 'title', 'description', 'category', 'status', 'odds_yes', 'odds_no', 'result', 'closes_at')
            ->withCount('bets');

        if ($filter === 'closed') {
            $query->whereIn('status', [Event::STATUS_RESOLVED, Event::STATUS_CANCELLED]);
        } elseif ($filter === 'all') {
            $query->whereIn('status', [Event::STATUS_OPEN, Event::STATUS_RESOLVED, Event::STATUS_CANCELLED]);
        } else {
            $query->where('status', Event::STATUS_OPEN);
        }

        if (array_key_exists($request->query('category'), Event::CATEGORIES)) {
            $query->where('category', $request->query('category'));
        }

        return EventResource::collection($query->orderBy('closes_at')->paginate(10));
    }

    #[OA\Get(
        path: '/events/{id}',
        summary: 'Afficher un événement',
        tags: ['Events'],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 4))],
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Event')]
            )),
            new OA\Response(response: 403, description: 'Événement non publié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 404, description: 'Introuvable', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
        ]
    )]
    public function show(Request $request, Event $event)
    {
        // Same rule as the website: pending events are only visible by their creator
        if (Gate::forUser($request->user('sanctum'))->denies('view', $event)) {
            return response()->json(['message' => 'This action is unauthorized.'], Response::HTTP_FORBIDDEN);
        }

        $event->loadCount('bets');

        return new EventResource($event);
    }

    #[OA\Post(
        path: '/events',
        summary: 'Proposer un événement',
        description: 'Réservé aux joueurs de grade Parieur ou plus. L\'événement reste en attente de validation par un administrateur.',
        tags: ['Events'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/EventRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Créé (en attente de validation)', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', ref: '#/components/schemas/Event')]
            )),
            new OA\Response(response: 401, description: 'Non authentifié', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 403, description: 'Grade insuffisant', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Erreur de validation', content: new OA\JsonContent(ref: '#/components/schemas/ValidationError')),
        ]
    )]
    public function store(Request $request)
    {
        Gate::authorize('create', Event::class);

        $request->validate([
            'title' => 'required|string|min:10|max:150',
            'description' => 'required|string|min:20|max:2000',
            'category' => 'required|in:'.implode(',', array_keys(Event::CATEGORIES)),
            'closes_at' => 'required|date|after:+1 hour|before:+1 year',
        ]);

        $event = new Event($request->only(['title', 'description', 'category', 'closes_at']));
        $event->user_id = $request->user()->id;
        $event->status = Event::STATUS_PENDING;
        $event->save();

        return (new EventResource($event->fresh()))->response()->setStatusCode(Response::HTTP_CREATED);
    }

    #[OA\Get(
        path: '/leaderboard',
        summary: 'Classement de la semaine',
        description: 'Top 10 des BetCoins gagnés depuis lundi.',
        tags: ['Events'],
        responses: [
            new OA\Response(response: 200, description: 'Succès', content: new OA\JsonContent(
                properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/LeaderboardRow'))]
            )),
        ]
    )]
    public function leaderboard()
    {
        $rows = Transaction::select('user_id')
            ->selectRaw('SUM(amount) as total')
            ->where('type', 'win')
            ->where('created_at', '>=', now()->startOfWeek())
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->with('user:id,name')
            ->limit(10)
            ->get();

        $data = [];
        foreach ($rows as $index => $row) {
            $data[] = [
                'rank' => $index + 1,
                'name' => $row->user->name ?? null,
                'betcoins_won' => (int) $row->total,
            ];
        }

        return response()->json(['data' => $data]);
    }
}
