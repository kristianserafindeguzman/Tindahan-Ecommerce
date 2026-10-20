<?php

namespace Tests\Feature;

use App\Events\RealtimeChanged;
use App\Models\ApprovalStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Contracts\Broadcasting\Broadcaster;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('Needs pdo_sqlite for the isolated in-memory database; no database or PHP configuration is changed.');
        }

        // Isolated connection: legacy MySQL-only migrations and real databases are untouched.
        config(['database.default' => 'realtime_test', 'database.connections.realtime_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
        ], 'app.key' => 'realtime-test-key']);
        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('role');
            $table->string('full_name');
            $table->string('account_status')->default('active');
            $table->timestamp('last_activity_at')->nullable();
            $table->softDeletes();
        });
        Schema::create('stores', function (Blueprint $table) {
            $table->increments('store_id');
            $table->unsignedInteger('owner_id');
            $table->string('store_name');
            $table->softDeletes();
        });
        Schema::create('approval_status', function (Blueprint $table) {
            $table->increments('approval_id');
            $table->unsignedInteger('store_id');
            $table->string('status');
            $table->text('rejection_reason')->nullable();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id('notification_id');
            $table->unsignedInteger('user_id');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedInteger('consumer_id');
            $table->unsignedInteger('store_id');
            $table->string('status');
            $table->timestamps();
        });
        Event::fake([RealtimeChanged::class]);
    }

    private function user(string $role = 'Consumer'): User
    {
        return User::forceCreate(['role' => $role, 'full_name' => $role, 'account_status' => 'active']);
    }

    private function vendor(string $status = 'approved'): array
    {
        $vendor = $this->user('Vendor');
        $store = Store::forceCreate(['owner_id' => $vendor->user_id, 'store_name' => 'Test Store']);
        $approval = ApprovalStatus::create(['store_id' => $store->store_id, 'status' => $status]);

        return [$vendor, $store, $approval];
    }

    private function authorizeChannel(string $channel)
    {
        return $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-'.$channel]);
    }

    private function useRealtimeBroadcaster(Broadcaster $broadcaster): void
    {
        config(['broadcasting.connections.reverb.driver' => 'realtime-test']);
        Broadcast::extend('realtime-test', fn () => $broadcaster);
        Broadcast::purge('reverb');
        Event::except([RealtimeChanged::class]);
        Queue::fake();
    }

    public function test_channels_require_authentication_and_ownership(): void
    {
        $consumer = $this->user();
        $other = $this->user();
        $this->authorizeChannel('users.'.$consumer->user_id)->assertUnauthorized();
        Sanctum::actingAs($consumer);
        $this->authorizeChannel('users.'.$consumer->user_id)->assertOk()->assertJsonStructure(['auth']);
        $this->authorizeChannel('users.'.$other->user_id)->assertForbidden();
        $this->authorizeChannel('vendor-applications.'.$consumer->user_id)->assertForbidden();
    }

    public function test_pending_vendors_only_join_their_application_channel(): void
    {
        [$vendor] = $this->vendor('pending');
        Sanctum::actingAs($vendor);
        $this->authorizeChannel('users.'.$vendor->user_id)->assertForbidden();
        $this->authorizeChannel('vendor-applications.'.$vendor->user_id)->assertOk();
        $this->getJson('/api/realtime/config')->assertOk()
            ->assertJsonPath('channels', ['vendor-applications.'.$vendor->user_id]);
        $this->getJson('/api/vendor/notifications')->assertForbidden();
    }

    public function test_local_socket_config_follows_the_server_port_and_allows_a_public_override(): void
    {
        Sanctum::actingAs($this->user());
        config(['realtime.server_port' => 9090]);
        $this->getJson('/api/realtime/config')->assertOk()->assertJsonPath('port', 9090);
        config(['realtime.public_scheme' => 'https']);
        $this->getJson('/api/realtime/config')->assertOk()->assertJsonPath('port', 443);
        config(['realtime.public_port' => 8443]);
        $this->getJson('/api/realtime/config')->assertOk()->assertJsonPath('port', 8443);
    }

    public function test_admin_and_approved_vendor_channels_are_private_and_suspended_accounts_are_denied(): void
    {
        [$vendor] = $this->vendor();
        $admin = $this->user('Admin');
        foreach ([$vendor, $admin] as $user) {
            Sanctum::actingAs($user);
            $this->authorizeChannel('users.'.$user->user_id)->assertOk();
            $user->update(['account_status' => 'suspended']);
            $this->authorizeChannel('users.'.$user->user_id)->assertForbidden();
            $this->getJson('/api/realtime/config')->assertForbidden()->assertJsonPath('error_code', 'ACCOUNT_SUSPENDED');
        }
    }

    public function test_notification_snapshot_counts_every_unread_record_and_preserves_the_legacy_array(): void
    {
        $consumer = $this->user();
        $other = $this->user();
        for ($i = 0; $i < 61; $i++) {
            Notification::create(['user_id' => $consumer->user_id, 'title' => 'Order Ready', 'message' => 'Ready']);
        }
        Notification::create(['user_id' => $other->user_id, 'title' => 'Private', 'message' => 'Private']);
        Sanctum::actingAs($consumer);
        $this->getJson('/api/consumer/notifications')->assertOk()->assertJsonCount(50)->assertJsonPath('0.is_read', false);
        $this->getJson('/api/consumer/notifications?sync=1', ['X-Background-Sync' => '1'])
            ->assertOk()->assertJsonCount(50, 'notifications')->assertJsonPath('unread_count', 61)->assertJsonPath('total', 61);
    }

    public function test_read_one_and_bulk_read_broadcast_and_cannot_modify_another_user(): void
    {
        $consumer = $this->user();
        $other = $this->user();
        $notice = Notification::create(['user_id' => $consumer->user_id, 'title' => 'Ready', 'message' => 'Ready']);
        $private = Notification::create(['user_id' => $other->user_id, 'title' => 'Ready', 'message' => 'Ready']);
        Event::fake([RealtimeChanged::class]);
        Sanctum::actingAs($consumer);
        $this->patchJson('/api/consumer/notifications/'.$private->notification_id.'/read')->assertNotFound();
        $this->patchJson('/api/consumer/notifications/'.$notice->notification_id.'/read')->assertOk();
        $this->postJson('/api/consumer/notifications/read-all')->assertOk();
        Event::assertDispatchedTimes(RealtimeChanged::class, 2);
        $this->assertFalse($private->fresh()->is_read);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) => $event->userIds === [$consumer->user_id] && $event->topic === 'notifications');
    }

    public function test_notification_signals_wait_for_commit_and_rollback_emits_nothing(): void
    {
        $consumer = $this->user();
        DB::beginTransaction();
        Notification::create(['user_id' => $consumer->user_id, 'title' => 'Ready', 'message' => 'Ready']);
        Event::assertNotDispatched(RealtimeChanged::class);
        DB::rollBack();
        Event::assertNotDispatched(RealtimeChanged::class);
        DB::beginTransaction();
        Notification::create(['user_id' => $consumer->user_id, 'title' => 'Ready', 'message' => 'Ready']);
        DB::commit();
        Event::assertDispatchedTimes(RealtimeChanged::class, 1);
    }

    public function test_order_creation_and_terminal_status_changes_notify_both_owners(): void
    {
        [$vendor, $store] = $this->vendor();
        $consumer = $this->user();
        Event::fake([RealtimeChanged::class]);
        $order = Order::create(['consumer_id' => $consumer->user_id, 'store_id' => $store->store_id, 'status' => 'placed']);
        $order->update(['status' => 'cancelled']);
        Event::assertDispatchedTimes(RealtimeChanged::class, 2);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) => $event->topic === 'orders' && $event->userIds === [$consumer->user_id, $vendor->user_id]);
    }

    public function test_order_status_signals_broadcast_after_commit_without_a_queue_worker(): void
    {
        [$vendor, $store] = $this->vendor();
        $consumer = $this->user();
        $order = Order::create(['consumer_id' => $consumer->user_id, 'store_id' => $store->store_id, 'status' => 'placed']);
        $deliveries = [];
        $broadcaster = \Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->andReturnUsing(function ($channels, $event, $payload) use (&$deliveries) {
            $deliveries[] = [
                'channels' => array_map(fn ($channel) => (string) $channel, $channels),
                'event' => $event,
                'payload' => $payload,
                'transaction_level' => DB::transactionLevel(),
            ];
        });
        $this->useRealtimeBroadcaster($broadcaster);

        DB::beginTransaction();
        $order->update(['status' => 'preparing']);
        $this->assertCount(0, $deliveries);
        DB::rollBack();
        $this->assertCount(0, $deliveries);

        DB::beginTransaction();
        $order->refresh()->update(['status' => 'preparing']);
        $this->assertCount(0, $deliveries);
        DB::commit();

        $this->assertCount(1, $deliveries);
        $this->assertSame(['private-users.'.$consumer->user_id, 'private-users.'.$vendor->user_id], $deliveries[0]['channels']);
        $this->assertSame('realtime.changed', $deliveries[0]['event']);
        $this->assertSame('orders', $deliveries[0]['payload']['topic']);
        $this->assertSame(0, $deliveries[0]['transaction_level']);
        Queue::assertNothingPushed();
    }

    public function test_immediate_broadcast_failure_does_not_undo_a_committed_status_change(): void
    {
        [, $store] = $this->vendor();
        $consumer = $this->user();
        $order = Order::create(['consumer_id' => $consumer->user_id, 'store_id' => $store->store_id, 'status' => 'placed']);
        $broadcaster = \Mockery::mock(Broadcaster::class);
        $broadcaster->shouldReceive('broadcast')->once()->andThrow(new \RuntimeException('Temporary realtime delivery outage'));
        $this->useRealtimeBroadcaster($broadcaster);
        Exceptions::fake();

        DB::transaction(fn () => $order->update(['status' => 'preparing']));

        $this->assertSame('preparing', $order->fresh()->status);
        Exceptions::assertReported(fn (\RuntimeException $error) => $error->getMessage() === 'Temporary realtime delivery outage');
        Queue::assertNothingPushed();
    }

    public function test_application_decisions_refresh_admins_and_the_restricted_vendor_channel(): void
    {
        $admin = $this->user('Admin');
        [$vendor, , $approval] = $this->vendor('pending');
        Event::fake([RealtimeChanged::class]);
        $approval->update(['status' => 'approved']);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) => $event->topic === 'applications' && $event->userIds === [$admin->user_id]);
        Event::assertDispatched(RealtimeChanged::class, fn ($event) => $event->topic === 'profile' && $event->applicationOnly && $event->userIds === [$vendor->user_id]);
    }

    public function test_public_config_and_signals_expose_no_secret_or_business_details_and_background_reads_do_not_touch_activity(): void
    {
        $consumer = $this->user();
        Sanctum::actingAs($consumer);
        $response = $this->getJson('/api/realtime/config')->assertOk();
        $this->assertArrayNotHasKey('secret', $response->json());
        $this->assertStringNotContainsString(config('broadcasting.connections.reverb.secret'), $response->getContent());
        $this->getJson('/api/consumer/notifications?sync=1', ['X-Background-Sync' => '1'])->assertOk();
        $this->assertNull($consumer->fresh()->last_activity_at);
        $event = new RealtimeChanged([$consumer->user_id], 'orders');
        $this->assertSame(['event_id', 'topic'], array_keys($event->broadcastWith()));
        $this->assertSame('private-users.'.$consumer->user_id, $event->broadcastOn()[0]->name);
    }
}
