<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminVendorSalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Isolated in-memory tables exercise actual queries without touching the app database
        // or running the project's older MySQL-specific migrations.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('full_name');
            $table->string('role');
            $table->string('account_status');
            $table->softDeletes();
        });
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            $table->string('store_name')->nullable();
            $table->softDeletes();
        });
        Schema::create('approval_status', function (Blueprint $table) {
            $table->id('approval_id');
            $table->unsignedBigInteger('store_id');
            $table->string('status');
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('consumer_id');
            $table->string('status');
            $table->decimal('total_amount', 12, 2);
            $table->timestamps();
        });
        Schema::create('order_items', function (Blueprint $table) {
            $table->id('order_item_id');
            $table->unsignedBigInteger('order_id');
            $table->integer('quantity');
        });
        DB::table('stores')->insert([['store_id' => 1], ['store_id' => 2]]);
        $this->actingRole('Admin');
    }

    private function actingRole(string $role): void
    {
        $user = new User();
        $user->forceFill(['user_id' => 10, 'role' => $role, 'account_status' => 'active']);
        Sanctum::actingAs($user);
    }

    private function sale(int $store, string $status, float $amount, string $time, int $units = 2, int $consumer = 20): int
    {
        $id = DB::table('orders')->insertGetId([
            'store_id' => $store, 'consumer_id' => $consumer, 'status' => $status,
            'total_amount' => $amount, 'created_at' => $time, 'updated_at' => $time,
        ], 'order_id');
        DB::table('order_items')->insert(['order_id' => $id, 'quantity' => $units]);
        return $id;
    }

    public function test_report_uses_store_sales_manila_dates_and_fresh_database_values(): void
    {
        $this->sale(1, 'picked_up', 100, '2026-10-04 16:00:00', 2);
        // A recorded walk-in sale uses the vendor as consumer and still counts.
        $this->sale(1, 'picked_up', 50, '2026-10-05 15:59:59', 3, 10);
        $this->sale(1, 'cancelled', 900, '2026-10-05 00:00:00');
        $this->sale(1, 'ready_for_pickup', 900, '2026-10-05 00:00:00');
        $this->sale(2, 'picked_up', 5000, '2026-10-05 00:00:00');
        $this->sale(1, 'picked_up', 5000, '2026-10-04 15:59:59');
        $this->sale(1, 'picked_up', 5000, '2026-10-05 16:00:00');
        $url = '/api/admin/vendors/1/sales?start_date=2026-10-05&end_date=2026-10-05';
        $this->getJson($url)->assertOk()
            ->assertJsonPath('metrics.revenue', 150)
            ->assertJsonPath('metrics.completed_orders', 2)
            ->assertJsonPath('metrics.units_sold', 5)
            ->assertJsonPath('metrics.average_order_value', 75)
            ->assertJsonPath('metrics.cancellation_rate', 25)
            ->assertJsonPath('daily.0.revenue', 150)
            ->assertJsonCount(2, 'recent_sales');
        $this->sale(1, 'picked_up', 25, '2026-10-05 01:00:00', 1);
        $this->getJson($url)->assertOk()->assertJsonPath('metrics.revenue', 175)->assertJsonPath('metrics.units_sold', 6);
    }

    public function test_empty_days_validation_deleted_history_and_missing_stores(): void
    {
        $this->getJson('/api/admin/vendors/1/sales?start_date=2026-10-01&end_date=2026-10-02')
            ->assertOk()->assertJsonPath('metrics.revenue', 0)->assertJsonCount(2, 'daily')->assertJsonCount(0, 'recent_sales');
        DB::table('stores')->where('store_id', 1)->update(['deleted_at' => '2026-10-01 00:00:00']);
        $this->getJson('/api/admin/vendors/1/sales')->assertOk()->assertJsonCount(30, 'daily');
        $this->getJson('/api/admin/vendors/999/sales')->assertNotFound();
        foreach ([
            'start_date=bad&end_date=2026-10-02',
            'start_date=2026-10-03&end_date=2026-10-02',
            'start_date=2026-01-01&end_date=2026-10-02',
            'start_date=2026-10-01',
        ] as $query) {
            $this->getJson('/api/admin/vendors/1/sales?' . $query)->assertUnprocessable();
        }
    }

    public function test_reports_are_admin_only(): void
    {
        foreach (['Vendor', 'Consumer'] as $role) {
            $this->actingRole($role);
            $this->getJson('/api/admin/vendors/1/sales')->assertForbidden();
        }
    }

    public function test_recent_sales_are_limited_and_ordered_but_metrics_include_every_sale(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->sale(1, 'picked_up', 10, sprintf('2026-10-05 01:%02d:00', $i), 1);
        }
        $this->getJson('/api/admin/vendors/1/sales?start_date=2026-10-05&end_date=2026-10-05')
            ->assertOk()->assertJsonCount(10, 'recent_sales')
            ->assertJsonPath('recent_sales.0.order_id', 12)
            ->assertJsonPath('recent_sales.9.order_id', 3)
            ->assertJsonPath('metrics.revenue', 120)
            ->assertJsonPath('metrics.completed_orders', 12);
    }

    private function rankedStore(int $id, string $approval = 'approved', string $status = 'active'): void
    {
        DB::table('users')->insert(['user_id' => $id, 'full_name' => "Owner {$id}", 'role' => 'Vendor', 'account_status' => $status]);
        DB::table('stores')->updateOrInsert(['store_id' => $id], ['owner_id' => $id, 'store_name' => "Store {$id}"]);
        DB::table('approval_status')->insert(['store_id' => $id, 'status' => $approval]);
    }

    public function test_rankings_include_zero_sales_exclude_unapproved_and_deleted_and_refresh(): void
    {
        $this->rankedStore(1);
        $this->rankedStore(2, 'approved', 'inactive');
        $this->rankedStore(3);
        $this->rankedStore(4, 'pending');
        $this->rankedStore(5);
        $this->rankedStore(6);
        DB::table('stores')->where('store_id', 5)->update(['deleted_at' => '2026-10-01 00:00:00']);
        DB::table('users')->where('user_id', 6)->update(['deleted_at' => '2026-10-01 00:00:00']);
        $this->sale(1, 'picked_up', 100, '2026-10-04 16:00:00');
        $this->sale(2, 'picked_up', 20, '2026-10-05 15:59:59');
        $this->sale(2, 'picked_up', 20, '2026-10-05 01:00:00');
        foreach ([4, 5, 6] as $id) $this->sale($id, 'picked_up', 5000, '2026-10-05 01:00:00');
        $this->sale(3, 'cancelled', 9000, '2026-10-05 01:00:00');
        $this->sale(3, 'ready_for_pickup', 9000, '2026-10-05 01:00:00');
        $this->sale(3, 'picked_up', 9000, '2026-10-05 16:00:00');
        $url = '/api/admin/vendors/performance?start_date=2026-10-05&end_date=2026-10-05';
        $this->getJson($url)->assertOk()->assertJsonPath('eligible_stores', 3)
            ->assertJsonPath('stores_without_sales', 1)->assertJsonCount(2, 'top')
            ->assertJsonPath('top.0.store_id', 1)->assertJsonPath('top.0.revenue', 100)
            ->assertJsonPath('least.0.store_id', 3)->assertJsonPath('least.0.revenue', 0);
        $this->getJson($url . '&metric=completed_orders')->assertOk()->assertJsonPath('top.0.store_id', 2);
        $this->sale(3, 'picked_up', 200, '2026-10-05 02:00:00');
        $this->getJson($url)->assertOk()->assertJsonPath('top.0.store_id', 3)->assertJsonPath('stores_without_sales', 0);
    }

    public function test_rankings_limit_lists_and_preserve_tied_ranks(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->rankedStore($i);
            $this->sale($i, 'picked_up', 10, '2026-10-05 01:00:00');
        }
        $this->getJson('/api/admin/vendors/performance?start_date=2026-10-05&end_date=2026-10-05')
            ->assertOk()->assertJsonCount(5, 'top')->assertJsonCount(5, 'least')
            ->assertJsonPath('eligible_stores', 12)->assertJsonPath('top.4.rank', 1);
    }

    public function test_rankings_validate_dates_metrics_and_admin_permissions(): void
    {
        $this->getJson('/api/admin/vendors/performance')->assertOk()->assertJsonPath('eligible_stores', 0);
        $this->getJson('/api/admin/vendors/performance?metric=profit')->assertUnprocessable();
        $this->getJson('/api/admin/vendors/performance?start_date=2026-10-01')->assertUnprocessable();
        $this->getJson('/api/admin/vendors/performance?start_date=2026-01-01&end_date=2026-10-01')->assertUnprocessable();
        foreach (['Vendor', 'Consumer'] as $role) {
            $this->actingRole($role);
            $this->getJson('/api/admin/vendors/performance')->assertForbidden();
        }
    }
}
