<?php

namespace Tests\Feature;

use App\Models\ApprovalStatus;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Covers the vendor's notifications: reading their own, marking them read, and hearing about new and cancelled orders. */
class VendorNotificationTest extends TestCase
{
    use RefreshesTestDatabase;

    private int $phoneCounter = 0;

    private function makeUser(string $role): User
    {
        $n = ++$this->phoneCounter;

        return User::create([
            'role' => $role,
            'full_name' => "{$role} {$n}",
            'email' => strtolower($role) . "{$n}@example.com",
            'phone_number' => sprintf('0917%07d', $n),
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ]);
    }

    private function makeStore(User $owner): Store
    {
        return Store::forceCreate([
            'owner_id' => $owner->user_id,
            'store_name' => 'Aling Nena Store',
            'store_picture' => 'stores/aling-nena.jpg',
            'opening_time' => '08:00:00',
            'closing_time' => '20:00:00',
            'latitude' => 14.5764,
            'longitude' => 121.0851,
        ]);
    }

    // Vendor routes only open for a store an admin has approved, so the vendor gets one.
    private function approvedVendor(): User
    {
        $vendor = $this->makeUser('Vendor');
        ApprovalStatus::forceCreate(['store_id' => $this->makeStore($vendor)->store_id, 'status' => 'approved']);

        return $vendor;
    }

    public function test_a_vendor_sees_only_their_own_notifications(): void
    {
        $vendor = $this->approvedVendor();
        $consumer = $this->makeUser('Consumer');
        Notification::create(['user_id' => $vendor->user_id, 'title' => 'New Order', 'message' => 'Order #1 was placed.']);
        Notification::create(['user_id' => $consumer->user_id, 'title' => 'Order Ready', 'message' => 'Your order is ready.']);

        Sanctum::actingAs($vendor);
        $response = $this->getJson('/api/vendor/notifications')->assertOk();

        $this->assertCount(1, $response->json());
        $this->assertSame('New Order', $response->json('0.title'));
    }

    public function test_a_vendor_can_mark_one_and_then_all_notifications_as_read(): void
    {
        $vendor = $this->approvedVendor();
        $first = Notification::create(['user_id' => $vendor->user_id, 'title' => 'New Order', 'message' => 'Order #1 was placed.']);
        Notification::create(['user_id' => $vendor->user_id, 'title' => 'New Order', 'message' => 'Order #2 was placed.']);

        Sanctum::actingAs($vendor);
        $this->patchJson("/api/vendor/notifications/{$first->notification_id}/read")->assertOk();
        $this->assertTrue((bool) $first->refresh()->is_read);

        $this->postJson('/api/vendor/notifications/read-all')->assertOk();
        $this->assertSame(0, Notification::where('user_id', $vendor->user_id)->where('is_read', false)->count());
    }

    public function test_a_consumer_cannot_use_the_vendor_notification_routes(): void
    {
        Sanctum::actingAs($this->makeUser('Consumer'));

        $this->getJson('/api/vendor/notifications')->assertForbidden();
    }

    public function test_placing_an_order_notifies_the_store_owner(): void
    {
        $vendor = $this->makeUser('Vendor');
        $consumer = $this->makeUser('Consumer');
        $store = $this->makeStore($vendor);
        $category = Category::forceCreate(['category_name' => 'Snacks']);
        $item = Inventory::forceCreate([
            'store_id' => $store->store_id,
            'category_id' => $category->category_id,
            'product_name' => 'SkyFlakes Crackers',
            'price' => 60,
            'stock_quantity' => 10,
            'status' => 'active',
        ]);
        CartItem::forceCreate(['consumer_id' => $consumer->user_id, 'inventory_id' => $item->inventory_id, 'quantity' => 2]);

        Sanctum::actingAs($consumer);
        $orderId = $this->postJson('/api/consumer/checkout', ['store_id' => $store->store_id])->assertCreated()->json('order.order_id');

        $notice = Notification::where('user_id', $vendor->user_id)->first();
        $this->assertNotNull($notice);
        $this->assertSame('New Order', $notice->title);
        $this->assertSame($orderId, (int) $notice->order_id);
        $this->assertStringContainsString('₱120.00', $notice->message);
    }

    public function test_a_customer_cancelling_notifies_the_store_owner(): void
    {
        $vendor = $this->makeUser('Vendor');
        $consumer = $this->makeUser('Consumer');
        $store = $this->makeStore($vendor);
        $order = Order::forceCreate([
            'consumer_id' => $consumer->user_id,
            'store_id' => $store->store_id,
            'total_amount' => 60,
            'status' => 'placed',
        ]);

        Sanctum::actingAs($consumer);
        $this->patchJson("/api/consumer/orders/{$order->order_id}/cancel")->assertOk();

        $notice = Notification::where('user_id', $vendor->user_id)->first();
        $this->assertNotNull($notice);
        $this->assertSame('Order Cancelled', $notice->title);
        $this->assertSame($order->order_id, (int) $notice->order_id);
        $this->assertTrue(Notification::where('user_id', $consumer->user_id)->where('title', 'Order Cancelled')->exists());
    }
}
