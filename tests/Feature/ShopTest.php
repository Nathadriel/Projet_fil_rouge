<?php

namespace Tests\Feature;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_fake',
            'services.stripe.webhook_secret' => 'whsec_test',
        ]);
    }

    protected function tearDown(): void
    {
        ApiRequestor::setHttpClient(null);
        parent::tearDown();
    }

    // Replaces the Stripe API with a fake answer (no real network call in the tests)
    private function fakeStripeApi(): void
    {
        ApiRequestor::setHttpClient(new class implements ClientInterface
        {
            public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
            {
                $body = json_encode([
                    'id' => 'cs_test_123',
                    'object' => 'checkout.session',
                    'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
                ]);

                return [$body, 200, []];
            }
        });
    }

    private function createPurchase(User $user, string $status = 'pending', string $sessionId = 'cs_test_123'): Purchase
    {
        $purchase = new Purchase(['pack' => 'pro', 'betcoins' => 1500, 'amount' => 499]);
        $purchase->user_id = $user->id;
        $purchase->status = $status;
        $purchase->stripe_session_id = $sessionId;
        $purchase->paid_at = $status === 'paid' ? now() : null;
        $purchase->save();

        return $purchase;
    }

    // Sends a webhook signed like Stripe does
    private function sendWebhook(array $event, string $secret = 'whsec_test')
    {
        $payload = json_encode($event);
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
        ], $payload);
    }

    private function paidSessionEvent(int $amount = 499): array
    {
        return [
            'id' => 'evt_test',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => [
                'id' => 'cs_test_123',
                'object' => 'checkout.session',
                'payment_status' => 'paid',
                'amount_total' => $amount,
            ]],
        ];
    }

    public function test_shop_shows_the_packs(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/shop')
            ->assertStatus(200)
            ->assertSee('Pack Starter')
            ->assertSee('4,99 €');
    }

    public function test_checkout_creates_a_purchase_and_redirects_to_stripe(): void
    {
        $this->fakeStripeApi();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/shop/checkout', ['pack' => 'pro']);

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');
        $this->assertDatabaseHas('purchases', [
            'user_id' => $user->id,
            'pack' => 'pro',
            'amount' => 499,
            'status' => 'pending',
            'stripe_session_id' => 'cs_test_123',
        ]);
    }

    public function test_unknown_pack_is_refused(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/shop/checkout', ['pack' => 'free'])->assertSessionHasErrors('pack');
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_webhook_gives_the_betcoins_only_once(): void
    {
        $user = User::factory()->create();
        $this->createPurchase($user);

        $this->sendWebhook($this->paidSessionEvent())->assertStatus(200);
        $this->sendWebhook($this->paidSessionEvent())->assertStatus(200);

        $this->assertEquals(1600, $user->fresh()->betcoins);
        $this->assertDatabaseHas('purchases', ['stripe_session_id' => 'cs_test_123', 'status' => 'paid']);
        $this->assertDatabaseCount('transactions', 1);
    }

    public function test_webhook_with_a_wrong_signature_is_refused(): void
    {
        $user = User::factory()->create();
        $this->createPurchase($user);

        $this->sendWebhook($this->paidSessionEvent(), 'whsec_hacker')->assertStatus(400);

        $this->assertEquals(100, $user->fresh()->betcoins);
    }

    public function test_webhook_with_a_wrong_amount_gives_nothing(): void
    {
        $user = User::factory()->create();
        $this->createPurchase($user);

        $this->sendWebhook($this->paidSessionEvent(1))->assertStatus(200);

        $this->assertEquals(100, $user->fresh()->betcoins);
    }

    public function test_daily_purchase_limit(): void
    {
        $user = User::factory()->create();
        $this->createPurchase($user, 'paid', 'cs_1');
        $this->createPurchase($user, 'paid', 'cs_2');
        $this->createPurchase($user, 'paid', 'cs_3');

        $this->actingAs($user)->post('/shop/checkout', ['pack' => 'starter'])->assertSessionHas('error');
        $this->assertDatabaseCount('purchases', 3);
    }
}
