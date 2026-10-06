<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VendorSalesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-07 12:00:00', 'Asia/Manila'));
        // Isolate the report queries from the application database and MySQL-only migrations.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('role');
            $table->string('account_status');
            $table->timestamp('last_activity_at')->nullable();
            $table->softDeletes();
        });
        Schema::create('stores', function (Blueprint $table) {
            $table->id('store_id');
            $table->unsignedBigInteger('owner_id');
            $table->softDeletes();
        });
        Schema::create('approval_status', function (Blueprint $table) {
            $table->id('approval_id');
            $table->unsignedBigInteger('store_id');
            $table->string('status');
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('category_name');
        });
        Schema::create('inventory', function (Blueprint $table) {
            $table->id('inventory_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('category_id');
            $table->string('product_name');
            $table->decimal('price', 8, 2)->default(10);
            $table->integer('stock_quantity')->default(10);
            $table->integer('reserved_quantity')->default(2);
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
            $table->unsignedBigInteger('inventory_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 8, 2)->nullable();
            $table->decimal('subtotal', 8, 2)->default(0);
        });
        DB::table('users')->insert(['user_id' => 10, 'role' => 'Vendor', 'account_status' => 'active']);
        DB::table('stores')->insert([
            ['store_id' => 1, 'owner_id' => 10],
            ['store_id' => 2, 'owner_id' => 20],
        ]);
        DB::table('approval_status')->insert(['store_id' => 1, 'status' => 'approved']);
        DB::table('categories')->insert(['category_id' => 1, 'category_name' => 'Beverages']);
        DB::table('inventory')->insert([
            'inventory_id' => 1, 'store_id' => 1, 'category_id' => 1, 'product_name' => 'Water',
        ]);
        Sanctum::actingAs(User::findOrFail(10));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function sale(string $time, int $store = 1, string $status = 'picked_up'): int
    {
        $id = DB::table('orders')->insertGetId([
            'store_id' => $store, 'consumer_id' => 10, 'status' => $status,
            'total_amount' => 20, 'created_at' => $time, 'updated_at' => $time,
        ], 'order_id');
        DB::table('order_items')->insert(['order_id' => $id, 'inventory_id' => 1, 'quantity' => 2]);

        return $id;
    }

    public function test_selected_day_returns_every_sale_in_stable_order_with_complete_totals(): void
    {
        $ids = [];
        // Include Manila midnight, the final second of the day, and tied walk-in timestamps.
        $ids[] = $this->sale('2026-10-05 16:00:00');
        for ($i = 0; $i < 31; $i++) {
            $ids[] = $this->sale('2026-10-06 01:00:00');
        }
        $ids[] = $this->sale('2026-10-06 15:59:59');
        $this->sale('2026-10-05 15:59:59');
        $this->sale('2026-10-06 16:00:00');
        $this->sale('2026-10-06 01:00:00', 2);
        $this->sale('2026-10-06 01:00:00', 1, 'cancelled');
        $this->sale('2026-10-06 01:00:00', 1, 'ready_for_pickup');

        $range = '?start_date=2026-10-06&end_date=2026-10-06';
        $response = $this->getJson('/api/vendor/sales/transactions' . $range)
            ->assertOk()->assertJsonCount(33)
            ->assertJsonPath('0.product', 'Water')
            ->assertJsonPath('0.quantity', 2)
            ->assertJsonPath('0.status', 'Picked up');
        $rows = collect($response->json());
        $this->assertSame(array_reverse($ids), $rows->pluck('order_id')->all());
        $this->assertEquals(66, $rows->sum('quantity'));
        $this->assertEquals(660, $rows->sum('total'));
        $this->getJson('/api/vendor/sales/metrics' . $range)->assertOk()
            ->assertJsonPath('revenue', 660)
            ->assertJsonPath('avg_order_value', 20);
    }

    public function test_all_time_returns_more_than_ten_daily_totals_and_empty_dates_return_no_rows(): void
    {
        for ($day = 1; $day <= 12; $day++) {
            $this->sale(sprintf('2026-09-%02d 16:00:00', $day));
        }
        $this->sale('2026-09-12 16:00:00');
        $this->sale('2026-09-12 16:00:00', 2);
        $this->sale('2026-09-12 16:00:00', 1, 'cancelled');

        $this->getJson('/api/vendor/sales/transactions')->assertOk()->assertJsonCount(12)
            ->assertJsonPath('0.sale_date', 'Sep 13, 2026')
            ->assertJsonPath('0.total_items', 4)
            ->assertJsonPath('0.daily_revenue', 40)
            ->assertJsonPath('11.sale_date', 'Sep 02, 2026');
        $this->getJson('/api/vendor/sales/transactions?start_date=2026-10-06&end_date=2026-10-06')
            ->assertOk()->assertExactJson([]);
    }

    public function test_report_endpoints_reject_invalid_incomplete_and_reversed_date_ranges(): void
    {
        foreach (['metrics', 'transactions'] as $endpoint) {
            foreach ([
                'start_date=invalid&end_date=2026-10-06',
                'start_date=2026-02-30&end_date=2026-10-06',
                'start_date=2026-10-07&end_date=2026-10-06',
                'start_date=2026-10-06',
                'end_date=2026-10-06',
            ] as $query) {
                $this->getJson('/api/vendor/sales/' . $endpoint . '?' . $query)->assertUnprocessable();
            }
        }
    }

    public function test_manual_sales_calculate_totals_from_quantity_and_price_and_use_the_selected_day(): void
    {
        $this->postJson('/api/vendor/sales/manual', [
            'inventory_id' => 1, 'quantity' => 2, 'unit_price' => 10,
            'total_amount' => 1, 'sale_date' => '2026-10-06',
        ])->assertOk();
        $this->assertDatabaseHas('orders', [
            'store_id' => 1, 'consumer_id' => 10, 'status' => 'picked_up',
            'total_amount' => 20, 'updated_at' => '2026-10-05 16:00:00',
        ]);
        $this->assertDatabaseHas('order_items', ['quantity' => 2, 'unit_price' => 10, 'subtotal' => 20]);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 8, 'reserved_quantity' => 2]);
        $this->getJson('/api/vendor/sales/metrics?start_date=2026-10-06&end_date=2026-10-06')
            ->assertOk()->assertJsonPath('revenue', 20);

        $this->postJson('/api/vendor/sales/manual', [
            'inventory_id' => 1, 'quantity' => 2, 'unit_price' => 0.29,
            'total_amount' => 0.58, 'sale_date' => '2026-10-06',
        ])->assertOk();
        $this->assertDatabaseHas('orders', ['total_amount' => 0.58]);
        $this->assertDatabaseHas('order_items', ['quantity' => 2, 'unit_price' => 0.29, 'subtotal' => 0.58]);
    }

    public function test_manual_sales_reject_prices_and_totals_the_database_cannot_store_without_changing_stock(): void
    {
        $base = ['inventory_id' => 1, 'quantity' => 1, 'unit_price' => 10, 'total_amount' => 1, 'sale_date' => '2026-10-06'];
        foreach ([
            ['unit_price' => 0.005],
            ['unit_price' => 1000000],
            ['unit_price' => 600000, 'quantity' => 2],
        ] as $changes) {
            $this->postJson('/api/vendor/sales/manual', array_replace($base, $changes))->assertUnprocessable();
        }
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 10]);

        $this->postJson('/api/vendor/sales/manual', array_replace($base, ['unit_price' => 999999.99]))->assertOk();
        $this->assertDatabaseHas('orders', ['total_amount' => 999999.99]);
        $this->assertDatabaseHas('order_items', ['unit_price' => 999999.99, 'subtotal' => 999999.99]);
    }

    public function test_manual_sales_preserve_reserved_stock_and_allow_zero_priced_sales(): void
    {
        $base = ['inventory_id' => 1, 'quantity' => 9, 'unit_price' => 10, 'total_amount' => 90, 'sale_date' => '2026-10-06'];
        $this->postJson('/api/vendor/sales/manual', $base)->assertStatus(400);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 10, 'reserved_quantity' => 2]);
        $this->postJson('/api/vendor/sales/manual', array_replace($base, [
            'quantity' => 8, 'unit_price' => 0, 'total_amount' => 0,
        ]))->assertOk();
        $this->assertDatabaseHas('orders', ['total_amount' => 0]);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 2, 'reserved_quantity' => 2]);
    }

    public function test_future_sales_are_rejected_without_changing_stock_and_today_and_past_dates_are_allowed(): void
    {
        $payload = ['inventory_id' => 1, 'quantity' => 1, 'unit_price' => 10, 'total_amount' => 10, 'sale_date' => '2026-10-08'];
        $this->postJson('/api/vendor/sales/manual', $payload)->assertUnprocessable()
            ->assertJsonValidationErrors(['sale_date']);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 10]);
        foreach (['2026-10-07', '2026-10-06'] as $day) {
            $this->postJson('/api/vendor/sales/manual', array_replace($payload, ['sale_date' => $day]))->assertOk();
        }
        $this->assertDatabaseCount('orders', 2);
        $this->assertDatabaseHas('inventory', ['inventory_id' => 1, 'stock_quantity' => 8]);
    }

    public function test_manual_sales_use_manila_midnight_instead_of_the_utc_calendar_day(): void
    {
        config(['app.timezone' => 'UTC']);
        Carbon::setTestNow(Carbon::parse('2026-10-06 16:15:00', 'UTC'));
        $payload = ['inventory_id' => 1, 'quantity' => 1, 'unit_price' => 10, 'total_amount' => 10, 'sale_date' => '2026-10-07'];
        $this->postJson('/api/vendor/sales/manual', $payload)->assertOk();
        $payload['sale_date'] = '2026-10-08';
        Carbon::setTestNow(Carbon::parse('2026-10-07 15:59:59', 'UTC'));
        $this->postJson('/api/vendor/sales/manual', $payload)->assertUnprocessable()->assertJsonValidationErrors(['sale_date']);
        Carbon::setTestNow(Carbon::parse('2026-10-07 16:00:00', 'UTC'));
        $this->postJson('/api/vendor/sales/manual', $payload)->assertOk();
        $this->assertDatabaseCount('orders', 2);
    }
}
