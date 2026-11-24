<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function userWithGradeParieur(): User
    {
        $user = User::factory()->create(['betcoins' => 1000]);

        foreach (Event::factory(3)->create() as $event) {
            app(BettingService::class)->placeBet($user, $event, 'yes', 1);
        }

        return $user;
    }

    private function eventData(): array
    {
        return [
            'title' => 'Un chat deviendra maire de Bordeaux',
            'description' => 'Un félin sera-t-il officiellement élu à la mairie de Bordeaux ?',
            'category' => 'insolite',
            'closes_at' => now()->addWeek()->format('Y-m-d H:i'),
        ];
    }

    public function test_home_and_event_pages_are_public(): void
    {
        $event = Event::factory()->create();

        $this->get('/')->assertStatus(200)->assertSee($event->title);
        $this->get('/events')->assertStatus(200)->assertSee($event->title);
        $this->get(route('events.show', $event))->assertStatus(200)->assertSee('Connectez-vous');
    }

    public function test_events_can_be_filtered_by_category(): void
    {
        $sport = Event::factory()->create(['category' => 'sport']);
        $tech = Event::factory()->create(['category' => 'tech']);

        $this->get('/events?category=sport')
            ->assertSee($sport->title)
            ->assertDontSee($tech->title);
    }

    public function test_novice_cannot_create_an_event(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('events.create'))->assertStatus(403);
        $this->actingAs($user)->post(route('events.store'), $this->eventData())->assertStatus(403);
    }

    public function test_parieur_can_create_an_event_which_stays_pending(): void
    {
        $user = $this->userWithGradeParieur();

        $this->actingAs($user)->post(route('events.store'), $this->eventData())
            ->assertRedirect(route('profile.show'));

        $this->assertDatabaseHas('events', [
            'title' => 'Un chat deviendra maire de Bordeaux',
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_change_status_or_odds_with_the_form(): void
    {
        $user = $this->userWithGradeParieur();

        $this->actingAs($user)->post(route('events.store'), $this->eventData() + ['status' => 'open', 'odds_yes' => 49]);

        $this->assertDatabaseHas('events', ['title' => 'Un chat deviendra maire de Bordeaux', 'status' => 'pending', 'odds_yes' => 2]);
    }

    public function test_event_creation_fails_with_invalid_data(): void
    {
        $user = $this->userWithGradeParieur();

        $this->actingAs($user)->post(route('events.store'), [
            'title' => 'court',
            'category' => 'cuisine',
            'closes_at' => now()->subDay(),
        ])->assertSessionHasErrors(['title', 'description', 'category', 'closes_at']);
    }

    public function test_pending_event_is_only_visible_by_its_creator(): void
    {
        $event = Event::factory()->create(['status' => Event::STATUS_PENDING]);

        $this->get(route('events.show', $event))->assertStatus(403);
        $this->actingAs(User::factory()->create())->get(route('events.show', $event))->assertStatus(403);
        $this->actingAs($event->user)->get(route('events.show', $event))->assertStatus(200);
    }
}
