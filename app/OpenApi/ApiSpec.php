<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    info: new OA\Info(
        title: 'BetOnMe API',
        version: '1.0.0',
        description: 'API de BetOnMe : paris fictifs en BetCoins. Toutes les requêtes doivent envoyer l\'en-tête Accept: application/json.'
    ),
    servers: [
        new OA\Server(url: 'http://127.0.0.1:8000/api/v1', description: 'Local'),
    ]
)]
#[OA\Tag(name: 'Auth', description: 'Inscription, connexion par token Sanctum, profil')]
#[OA\Tag(name: 'Events', description: 'Événements et classement')]
#[OA\Tag(name: 'Bets', description: 'Paris de l\'utilisateur connecté')]
#[OA\SecurityScheme(
    securityScheme: 'BearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
    description: 'Utilise le token retourné par /login : Authorization: Bearer <token>'
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 3),
        new OA\Property(property: 'name', type: 'string', example: 'Tom'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'tom@betonme.test'),
        new OA\Property(property: 'betcoins', type: 'integer', example: 100),
        new OA\Property(property: 'grade', type: 'string', example: 'Novice'),
        new OA\Property(property: 'next_grade', type: 'string', nullable: true, example: 'Parieur'),
        new OA\Property(property: 'can_create_events', type: 'boolean', example: false),
    ]
)]
#[OA\Schema(
    schema: 'Event',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 4),
        new OA\Property(property: 'title', type: 'string', example: 'Il neigera à Marseille avant le 31 décembre'),
        new OA\Property(property: 'description', type: 'string', example: 'Météo-France relèvera-t-il au moins un flocon ?'),
        new OA\Property(property: 'category', type: 'string', example: 'environnement'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'open', 'refused', 'resolved', 'cancelled'], example: 'open'),
        new OA\Property(property: 'odds_yes', type: 'number', example: 6.0),
        new OA\Property(property: 'odds_no', type: 'number', example: 1.1),
        new OA\Property(property: 'result', type: 'string', nullable: true, enum: ['yes', 'no'], example: null),
        new OA\Property(property: 'closes_at', type: 'string', format: 'date-time', example: '2026-12-02T20:00:00+01:00'),
        new OA\Property(property: 'bets_count', type: 'integer', example: 6),
        new OA\Property(property: '_links', type: 'object', properties: [
            new OA\Property(property: 'self', type: 'string', format: 'uri', example: 'http://127.0.0.1:8000/api/v1/events/4'),
            new OA\Property(property: 'bet', type: 'string', format: 'uri', example: 'http://127.0.0.1:8000/api/v1/events/4/bets'),
            new OA\Property(property: 'all', type: 'string', format: 'uri', example: 'http://127.0.0.1:8000/api/v1/events'),
        ]),
    ]
)]
#[OA\Schema(
    schema: 'EventCollection',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Event')),
        new OA\Property(property: 'links', type: 'object'),
        new OA\Property(property: 'meta', type: 'object'),
    ]
)]
#[OA\Schema(
    schema: 'EventRequest',
    type: 'object',
    required: ['title', 'description', 'category', 'closes_at'],
    properties: [
        new OA\Property(property: 'title', type: 'string', example: 'Un chat deviendra maire de Bordeaux'),
        new OA\Property(property: 'description', type: 'string', example: 'Un félin sera-t-il officiellement élu à la mairie ?'),
        new OA\Property(property: 'category', type: 'string', enum: ['politique', 'tech', 'sport', 'environnement', 'insolite'], example: 'insolite'),
        new OA\Property(property: 'closes_at', type: 'string', format: 'date-time', example: '2026-12-20 20:00'),
    ]
)]
#[OA\Schema(
    schema: 'Bet',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'choice', type: 'string', enum: ['yes', 'no'], example: 'yes'),
        new OA\Property(property: 'amount', type: 'integer', example: 20),
        new OA\Property(property: 'odds', type: 'number', example: 3.6),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'won', 'lost', 'refunded'], example: 'pending'),
        new OA\Property(property: 'potential_gain', type: 'integer', example: 72),
        new OA\Property(property: 'payout', type: 'integer', example: 0),
        new OA\Property(property: 'event', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 6),
            new OA\Property(property: 'title', type: 'string', example: 'Une nouvelle console sera annoncée par surprise ce mois-ci'),
            new OA\Property(property: 'link', type: 'string', format: 'uri', example: 'http://127.0.0.1:8000/api/v1/events/6'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ]
)]
#[OA\Schema(
    schema: 'BetRequest',
    type: 'object',
    required: ['choice', 'amount'],
    properties: [
        new OA\Property(property: 'choice', type: 'string', enum: ['yes', 'no'], example: 'yes'),
        new OA\Property(property: 'amount', type: 'integer', example: 20),
    ]
)]
#[OA\Schema(
    schema: 'LeaderboardRow',
    type: 'object',
    properties: [
        new OA\Property(property: 'rank', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Lea'),
        new OA\Property(property: 'betcoins_won', type: 'integer', example: 150),
    ]
)]
#[OA\Schema(
    schema: 'RegisterRequest',
    type: 'object',
    required: ['name', 'email', 'password'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Nathou'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nathou@example.com'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Sup3r-Secret!Pass'),
    ]
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'tom@betonme.test'),
        new OA\Property(property: 'password', type: 'string', format: 'password', example: 'Password!2026'),
    ]
)]
#[OA\Schema(
    schema: 'LoginResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'access_token', type: 'string', example: '1|xYz...token...AbC'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ]
)]
#[OA\Schema(
    schema: 'MessageResponse',
    type: 'object',
    properties: [new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.')]
)]
#[OA\Schema(
    schema: 'ValidationError',
    type: 'object',
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Le champ choix est obligatoire.'),
        new OA\Property(property: 'errors', type: 'object', example: ['choice' => ['Le champ choix est obligatoire.']]),
    ]
)]
class ApiSpec {}
