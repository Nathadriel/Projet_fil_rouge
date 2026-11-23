<?php

namespace Tests\Feature;

use App\Enums\BetChoice;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventTest extends TestCase
{
    use RefreshDatabase;

    private function userWithBets(int $count): User
    {
        $user = User::factory()->withCoins(1000)->create();
        $betting = app(BettingService::class);
        Event::factory($count)->create()->each(fn ($e) => $betting->placeBet($user, $e, BetChoice::Yes, 1));

        return $user;
    }

    private function validEvent(): array
    {
        return [
            'title' => 'Un chat deviendra maire de Bordeaux',
            'description' => 'Un félin sera-t-il officiellement élu à la mairie de Bordeaux ?',
            'category' => 'insolite',
            'closes_at' => now()->addWeek()->format('Y-m-d H:i'),
        ];
    }

    public function test_home_and_event_list_are_public(): void
    {
        $event = Event::factory()->create();

        $this->get('/')->assertOk()->assertSee($event->title);
        $this->get('/evenements')->assertOk()->assertSee($event->title);
        $this->get(route('events.show', $event))->assertOk()->assertSee('Connectez-vous');
    }

    public function test_novice_cannot_create_event(): void
    {
        $novice = User::factory()->create();

        $this->actingAs($novice)->get(route('events.create'))->assertForbidden();
        $this->actingAs($novice)->post(route('events.store'), $this->validEvent())->assertForbidden();
    }

    public function test_parieur_can_propose_event_which_stays_pending(): void
    {
        $user = $this->userWithBets(3);

        $this->actingAs($user)->post(route('events.store'), $this->validEvent())->assertRedirect(route('profile.show'));

        $event = Event::where('title', 'Un chat deviendra maire de Bordeaux')->firstOrFail();
        $this->assertSame(EventStatus::Pending, $event->status);
        $this->assertSame($user->id, $event->user_id);
    }

    public function test_user_cannot_force_status_or_odds_via_mass_assignment(): void
    {
        $user = $this->userWithBets(3);

        $this->actingAs($user)->post(route('events.store'), $this->validEvent() + ['status' => 'open', 'odds_yes' => 49]);

        $event = Event::where('title', 'Un chat deviendra maire de Bordeaux')->firstOrFail();
        $this->assertSame(EventStatus::Pending, $event->status);
        $this->assertEquals(2.0, (float) $event->odds_yes);
    }

    public function test_event_validation_rules(): void
    {
        $user = $this->userWithBets(3);

        $this->actingAs($user)->post(route('events.store'), ['title' => 'court', 'category' => 'cuisine', 'closes_at' => now()->subDay()])
            ->assertSessionHasErrors(['title', 'description', 'category', 'closes_at']);
    }

    public function test_pending_event_is_hidden_from_other_users(): void
    {
        $event = Event::factory()->pending()->create();

        $this->get(route('events.show', $event))->assertForbidden();
        $this->actingAs(User::factory()->create())->get(route('events.show', $event))->assertForbidden();
        $this->actingAs($event->creator)->get(route('events.show', $event))->assertOk();
    }

    public function test_admin_validates_event_with_odds(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.events.approve', $event), ['odds_yes' => 3.2, 'odds_no' => 1.4])
            ->assertSessionHas('success');

        $event->refresh();
        $this->assertSame(EventStatus::Open, $event->status);
        $this->assertEquals(3.2, (float) $event->odds_yes);
    }

    public function test_admin_refuses_event(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->pending()->create();

        $this->actingAs($admin)->post(route('admin.events.refuse', $event));

        $this->assertSame(EventStatus::Refused, $event->fresh()->status);
    }

    public function test_back_office_is_forbidden_to_regular_users(): void
    {
        $event = Event::factory()->pending()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->actingAs($user)->post(route('admin.events.approve', $event), ['odds_yes' => 2, 'odds_no' => 2])->assertForbidden();
        $this->assertSame(EventStatus::Pending, $event->fresh()->status);
    }

    public function test_admin_dashboard_lists_pending_events(): void
    {
        $admin = User::factory()->admin()->create();
        $event = Event::factory()->pending()->create();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee($event->title);
    }
}
