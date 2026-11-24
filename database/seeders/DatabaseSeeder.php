<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Database\Seeder;

/**
 * Demo data: php artisan migrate:fresh --seed
 *
 * Accounts (password: Password!2026)
 *  - admin@betonme.test (admin)
 *  - lea@betonme.test   (grade Parieur, can create events)
 *  - tom@betonme.test   (grade Novice)
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(BettingService $bettingService): void
    {
        // Permissions -> roles first, then users
        $this->call(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin@betonme.test', 'betcoins' => 1000]);
        $admin->assignRole('admin');

        $lea = User::factory()->create(['name' => 'Lea', 'email' => 'lea@betonme.test', 'betcoins' => 500]);
        $tom = User::factory()->create(['name' => 'Tom', 'email' => 'tom@betonme.test']);
        $players = User::factory(6)->create(['betcoins' => 300]);

        foreach ([$lea, $tom, ...$players] as $user) {
            $user->assignRole('user');
        }

        foreach ([$admin, $lea, $tom, ...$players] as $user) {
            $bettingService->addTransaction($user, 'signup_bonus', 100, 'Bienvenue sur BetOnMe !');
        }

        $events = [
            ['Un ministre viendra en trottinette électrique au Conseil des ministres', 'politique', 'Un membre du gouvernement arrivera-t-il à l’Élysée en trottinette électrique, casque compris, avant la fin du mois ? Une photo officielle fera foi.', 2.8, 1.4, $admin],
            ['Une IA sera candidate aux prochaines municipales', 'tech', 'Un parti ou un collectif présentera-t-il officiellement une intelligence artificielle comme tête de liste dans une ville française ?', 4.5, 1.15, $lea],
            ['Le PSG gagnera son prochain match avec plus de 3 buts d’écart', 'sport', 'Le Paris Saint-Germain remportera-t-il son prochain match officiel avec au moins 4 buts d’écart ?', 3.2, 1.3, $admin],
            ['Il neigera à Marseille avant le 31 décembre', 'environnement', 'Météo-France relèvera-t-il au moins un flocon à la station Marseille-Marignane avant la Saint-Sylvestre ?', 6.0, 1.1, $lea],
            ['Un pigeon volera la vedette lors d’un JT national', 'insolite', 'Un pigeon apparaîtra-t-il à l’écran pendant plus de 5 secondes lors d’un journal télévisé de 20 h ?', 2.2, 1.7, $admin],
            ['Une nouvelle console sera annoncée par surprise ce mois-ci', 'tech', 'Un grand constructeur annoncera-t-il une console de salon sans aucune fuite préalable ?', 3.6, 1.25, $lea],
            ['Le mot « baguette » sera tendance mondiale sur les réseaux', 'insolite', 'Le hashtag #baguette entrera-t-il dans le top 10 mondial des tendances pendant au moins une heure ?', 2.5, 1.5, $admin],
        ];

        $created = [];
        foreach ($events as $index => [$title, $category, $description, $oddsYes, $oddsNo, $creator]) {
            $created[] = Event::factory()->create([
                'user_id' => $creator->id,
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'closes_at' => now()->addDays(3 + $index * 2)->setTime(20, 0),
                'odds_yes' => $oddsYes,
                'odds_no' => $oddsNo,
            ]);
        }

        // Demo bets
        foreach ($players as $i => $player) {
            foreach (array_slice($created, 0, 4) as $j => $event) {
                $choice = ($i + $j) % 2 ? 'yes' : 'no';
                $bettingService->placeBet($player, $event, $choice, 10 + 5 * $i);
            }
        }

        // Lea bets on 3 events created by the admin, so she reaches the grade "Parieur"
        foreach ([$created[2], $created[4], $created[6]] as $event) {
            $bettingService->placeBet($lea, $event, 'yes', 30);
        }

        // Two resolved events for the leaderboard and one cancelled event
        $bettingService->resolveEvent($created[0], 'no');
        $bettingService->resolveEvent($created[1], 'yes');
        $bettingService->cancelEvent($created[2]);

        // Events waiting for the admin validation
        Event::factory()->create([
            'user_id' => $lea->id,
            'title' => 'Le prochain iPhone sera livré avec un chargeur… à manivelle',
            'description' => 'Proposition de la communauté, en attente de relecture et de fixation des cotes par un administrateur.',
            'category' => 'tech',
            'status' => Event::STATUS_PENDING,
        ]);
        Event::factory()->create([
            'user_id' => $lea->id,
            'title' => 'Un député fera son discours entièrement en alexandrins',
            'description' => 'Proposition de la communauté, en attente de relecture et de fixation des cotes par un administrateur.',
            'category' => 'politique',
            'status' => Event::STATUS_PENDING,
        ]);
    }
}
