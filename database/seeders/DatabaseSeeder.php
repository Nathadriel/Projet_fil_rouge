<?php

namespace Database\Seeders;

use App\Enums\BetChoice;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration : php artisan migrate:fresh --seed
 *
 * Comptes créés (mot de passe commun : Password!2026)
 *  - admin@betonme.test  (administrateur)
 *  - lea@betonme.test    (grade Parieur, peut proposer des événements)
 *  - tom@betonme.test    (Novice)
 */
class DatabaseSeeder extends Seeder
{
    public function run(BettingService $betting): void
    {
        $admin = User::factory()->admin()->withCoins(1000)->create(['name' => 'Admin', 'email' => 'admin@betonme.test']);
        $lea = User::factory()->withCoins(500)->create(['name' => 'Lea', 'email' => 'lea@betonme.test']);
        $tom = User::factory()->create(['name' => 'Tom', 'email' => 'tom@betonme.test']);
        $players = User::factory(6)->withCoins(300)->create();

        foreach ([$admin, $lea, $tom, ...$players] as $user) {
            $betting->grantSignupBonus($user);
        }

        $catalog = [
            ['Un ministre viendra en trottinette électrique au Conseil des ministres', 'politique', 'Un membre du gouvernement arrivera-t-il à l’Élysée en trottinette électrique, casque compris, avant la fin du mois ? Une photo officielle fera foi.', 2.8, 1.4],
            ['Une IA sera candidate aux prochaines municipales', 'tech', 'Un parti ou un collectif présentera-t-il officiellement une intelligence artificielle comme tête de liste dans une ville française ?', 4.5, 1.15],
            ['Le PSG gagnera son prochain match avec plus de 3 buts d’écart', 'sport', 'Le Paris Saint-Germain remportera-t-il son prochain match officiel avec au moins 4 buts d’écart ?', 3.2, 1.3],
            ['Il neigera à Marseille avant le 31 décembre', 'environnement', 'Météo-France relèvera-t-il au moins un flocon à la station Marseille-Marignane avant la Saint-Sylvestre ?', 6.0, 1.1],
            ['Un pigeon volera la vedette lors d’un JT national', 'insolite', 'Un pigeon apparaîtra-t-il à l’écran pendant plus de 5 secondes lors d’un journal télévisé de 20 h ?', 2.2, 1.7],
            ['Une nouvelle console sera annoncée par surprise ce mois-ci', 'tech', 'Un grand constructeur annoncera-t-il une console de salon sans aucune fuite préalable ?', 3.6, 1.25],
            ['Le mot « baguette » sera tendance mondiale sur les réseaux', 'insolite', 'Le hashtag #baguette entrera-t-il dans le top 10 mondial des tendances pendant au moins une heure ?', 2.5, 1.5],
        ];

        $events = collect($catalog)->map(function ($data, $i) use ($admin, $lea) {
            [$title, $category, $description, $yes, $no] = $data;
            $event = new Event([
                'title' => $title, 'description' => $description, 'category' => $category,
                'closes_at' => now()->addDays(3 + $i * 2)->setTime(20, 0),
            ]);
            $event->creator()->associate($i % 2 ? $lea : $admin);
            $event->status = EventStatus::Open;
            $event->odds_yes = $yes;
            $event->odds_no = $no;
            $event->save();

            return $event;
        });

        // Paris de démonstration
        foreach ($players as $i => $player) {
            foreach ($events->take(4) as $j => $event) {
                $betting->placeBet($player, $event, ($i + $j) % 2 ? BetChoice::Yes : BetChoice::No, 10 + 5 * $i);
            }
        }
        foreach ($events->where('user_id', '!==', $lea->id)->skip(1) as $event) {
            $betting->placeBet($lea, $event, BetChoice::Yes, 30);
        }

        // Deux événements résolus pour alimenter le classement de la semaine, un annulé
        $betting->resolveEvent($events[0], BetChoice::No);
        $betting->resolveEvent($events[1], BetChoice::Yes);
        $betting->cancelEvent($events[2]);

        // Propositions en attente de validation par l'admin
        foreach ([
            ['Le prochain iPhone sera livré avec un chargeur… à manivelle', 'tech'],
            ['Un député fera son discours entièrement en alexandrins', 'politique'],
        ] as [$title, $category]) {
            $event = new Event([
                'title' => $title, 'category' => $category,
                'description' => 'Proposition de la communauté, en attente de relecture et de fixation des cotes par un administrateur.',
                'closes_at' => now()->addWeeks(2),
            ]);
            $event->creator()->associate($lea);
            $event->status = EventStatus::Pending;
            $event->save();
        }
    }
}
