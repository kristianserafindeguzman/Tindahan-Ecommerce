<?php

namespace Tests\Feature;

use App\Models\ApprovalStatus;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Carbon\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/**
 * Covers "Schedule for later" at checkout: the chosen time is saved and reaches the vendor, a time
 * outside the store's hours is refused, and a scheduled order is not auto-cancelled before its time.
 */
class ScheduledPickupTest extends TestCase
{
    use RefreshesTestDatabase;

    private int $phoneCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Tuesday 29 Sep 2026, 10:00 in Manila: the store below is open now.
        Carbon::setTestNow(Carbon::parse('2026-09-29 10:00:00', 'Asia/Manila'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function makeUser(string $role): User
    {
        $n = ++$this->phoneCounter;

        return User::create([
            'role' => $role,
            'full_name' => "{$role} {$n}",
            'email' => strtolower($role) . "{$n}" . uniqid() . '@example.com',
            'phone_number' => sprintf('0916%07d', $n),
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ]);
    }

    /** Open Tuesday 08:00–20:00, closed Wednesday, open Thursday 09:00–17:00. */
    private function makeStore(User $owner): Store
    {
        $store = Store::forceCreate([
            'owner_id' => $owner->user_id,
            'store_name' => 'Aling Nena Store',
            'store_picture' => 'stores/aling-nena.jpg',
            'opening_time' => '08:00:00',
            'closing_time' => '20:00:00',
            'latitude' => 14.5764,
            'longitude' => 121.0851,
            'operating_days' => [
                'Tuesday' => ['is_open' => true, 'opening_time' => '08:00', 'closing_time' => '20:00'],
                'Wednesday' => ['is_open' => false, 'opening_time' => null, 'closing_time' => null],
                'Thursday' => ['is_open' => true, 'opening_time' => '09:00', 'closing_time' => '17:00'],
            ],
        ]);
        ApprovalStatus::forceCreate(['store_id' => $store->store_id, 'status' => 'approved']);

        return $store;
    }

    /** A consumer with one product from the store in their cart, ready to check out. */
    private function consumerWithCart(Store $store): User
    {
        $consumer = $this->makeUser('Consumer');
        $category = Category::forceCreate(['category_name' => 'Snacks ' . uniqid()]);
        $item = Inventory::forceCreate([
            'store_id' => $store->store_id,
            'category_id' => $category->category_id,
            'product_name' => 'SkyFlakes Crackers',
            'price' => 60,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
        CartItem::forceCreate(['consumer_id' => $consumer->user_id, 'inventory_id' => $item->inventory_id, 'quantity' => 2]);

        return $consumer;
    }

    private function checkout(User $consumer, Store $store, ?string $pickupAt)
    {
        Sanctum::actingAs($consumer);

        return $this->postJson('/api/consumer/checkout', [
            'store_id' => $store->store_id,
            'consumer_latitude' => 14.57,
            'consumer_longitude' => 121.08,
            'scheduled_pickup_at' => $pickupAt,
        ]);
    }

    public function test_an_asap_order_has_no_scheduled_time(): void
    {
        $store = $this->makeStore($this->makeUser('Vendor'));

        $orderId = $this->checkout($this->consumerWithCart($store), $store, null)->assertCreated()->json('order.order_id');

        $this->assertNull(Order::find($orderId)->scheduled_pickup_at);
    }

    public function test_a_scheduled_time_is_saved_and_shown_to_the_vendor(): void
    {
        $vendor = $this->makeUser('Vendor');
        $store = $this->makeStore($vendor);

        // Thursday 15:00 Manila, inside that day's 09:00–17:00.
        $orderId = $this->checkout($this->consumerWithCart($store), $store, '2026-10-01T15:00:00+08:00')
            ->assertCreated()
            ->json('order.order_id');

        $saved = Order::find($orderId)->scheduled_pickup_at;
        $this->assertTrue($saved->equalTo(Carbon::parse('2026-10-01T15:00:00+08:00')));

        // The store's "New Order" notice says when it is for, in Manila time.
        $notice = \App\Models\Notification::where('user_id', $vendor->user_id)->where('order_id', $orderId)->first();
        $this->assertStringContainsString('Pickup: Thu, Oct 1, 3:00 PM', $notice->message);

        Sanctum::actingAs($vendor);
        $list = $this->getJson('/api/vendor/orders')->assertOk()->json();
        $this->assertCount(1, $list);
        $this->assertNotNull($list[0]['scheduled_pickup_at']);
        $this->assertNotNull($this->getJson("/api/vendor/orders/{$orderId}")->assertOk()->json('scheduled_pickup_at'));
    }

    public function test_a_time_when_the_store_is_closed_is_refused(): void
    {
        $store = $this->makeStore($this->makeUser('Vendor'));

        // Wednesday: closed all day.
        $this->checkout($this->consumerWithCart($store), $store, '2026-09-30T12:00:00+08:00')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PICKUP_TIME_CLOSED');

        // Thursday 18:00: after that day's closing time.
        $this->checkout($this->consumerWithCart($store), $store, '2026-10-01T18:00:00+08:00')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PICKUP_TIME_CLOSED');

        $this->assertSame(0, Order::count());
    }

    public function test_a_past_or_too_distant_time_is_refused(): void
    {
        $store = $this->makeStore($this->makeUser('Vendor'));

        $this->checkout($this->consumerWithCart($store), $store, '2026-09-29T09:00:00+08:00')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PICKUP_TIME_INVALID');

        // Next Tuesday is open, but further ahead than checkout offers.
        $this->checkout($this->consumerWithCart($store), $store, '2026-10-06T10:00:00+08:00')
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'PICKUP_TIME_INVALID');
    }

    public function test_the_stores_list_includes_the_weekly_hours(): void
    {
        $store = $this->makeStore($this->makeUser('Vendor'));

        $hours = collect($this->getJson('/api/stores')->assertOk()->json())
            ->firstWhere('id', $store->store_id)['hours'];

        $this->assertSame(['opens' => '09:00', 'closes' => '17:00'], $hours['Thursday']);
        $this->assertNull($hours['Wednesday']);
        $this->assertNull($hours['Monday']);
    }

    public function test_a_scheduled_order_is_not_auto_cancelled_before_its_pickup_time(): void
    {
        $store = $this->makeStore($this->makeUser('Vendor'));
        $consumer = $this->makeUser('Consumer');

        // Both placed four hours ago, past the three-hour preparation window.
        $placed = Carbon::now()->subHours(4);
        $asap = Order::forceCreate([
            'consumer_id' => $consumer->user_id, 'store_id' => $store->store_id, 'total_amount' => 60,
            'status' => 'placed', 'created_at' => $placed, 'updated_at' => $placed,
        ]);
        $scheduled = Order::forceCreate([
            'consumer_id' => $consumer->user_id, 'store_id' => $store->store_id, 'total_amount' => 60,
            'status' => 'placed', 'created_at' => $placed, 'updated_at' => $placed,
            // Saved in the app's time zone, as checkout does.
            'scheduled_pickup_at' => Carbon::parse('2026-10-01 15:00:00', 'Asia/Manila')->setTimezone(config('app.timezone')),
        ]);

        $this->artisan('orders:auto-cancel')->assertSuccessful();

        $this->assertSame('cancelled', $asap->refresh()->status);
        $this->assertSame('placed', $scheduled->refresh()->status);

        // Three hours after its pickup time with nothing done, it is cancelled like any other.
        Carbon::setTestNow(Carbon::parse('2026-10-01 18:30:00', 'Asia/Manila'));
        $this->artisan('orders:auto-cancel')->assertSuccessful();
        $this->assertSame('cancelled', $scheduled->refresh()->status);
    }
}
