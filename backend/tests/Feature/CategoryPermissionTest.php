<?php

namespace Tests\Feature;

use App\Models\ApprovalStatus;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Categories are shared by every store: vendors and admins add and describe them, consumers can't touch them, and only an admin deletes one. */
class CategoryPermissionTest extends TestCase
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
            'phone_number' => sprintf('0918%07d', $n),
            'password_hash' => 'secret123',
            'account_status' => 'active',
        ]);
    }

    // Vendor routes only open for a store an admin has approved, so the vendor gets one.
    private function approvedVendor(): User
    {
        $vendor = $this->makeUser('Vendor');
        $store = Store::forceCreate([
            'owner_id' => $vendor->user_id,
            'store_name' => 'Aling Nena Store',
            'store_picture' => 'stores/aling-nena.jpg',
            'opening_time' => '08:00:00',
            'closing_time' => '20:00:00',
            'latitude' => 14.5764,
            'longitude' => 121.0851,
        ]);
        ApprovalStatus::forceCreate(['store_id' => $store->store_id, 'status' => 'approved']);

        return $vendor;
    }

    public function test_a_vendor_can_add_and_describe_a_category_but_not_delete_one(): void
    {
        Sanctum::actingAs($this->approvedVendor());

        $id = $this->postJson('/api/categories', ['category_name' => 'Frozen Goods'])->assertCreated()->json('category.category_id');
        $this->patchJson("/api/categories/{$id}", ['description' => 'Ice candy and ice cream'])->assertOk();

        $this->deleteJson("/api/categories/{$id}")->assertForbidden();
        $this->assertDatabaseHas('categories', ['category_id' => $id]);
    }

    public function test_a_consumer_cannot_add_change_or_delete_a_category(): void
    {
        $category = Category::forceCreate(['category_name' => 'Snacks', 'description' => 'Chips']);
        Sanctum::actingAs($this->makeUser('Consumer'));

        $this->postJson('/api/categories', ['category_name' => 'Frozen Goods'])->assertForbidden();
        $this->patchJson("/api/categories/{$category->category_id}", ['description' => 'Changed'])->assertForbidden();
        $this->deleteJson("/api/categories/{$category->category_id}")->assertForbidden();

        $this->assertDatabaseMissing('categories', ['category_name' => 'Frozen Goods']);
        $this->assertDatabaseHas('categories', ['category_id' => $category->category_id, 'description' => 'Chips']);
    }

    // An admin has no store, which a route open to "Admin,Vendor" must not hold against them.
    public function test_an_admin_can_add_describe_and_delete_a_category(): void
    {
        Sanctum::actingAs($this->makeUser('Admin'));

        $id = $this->postJson('/api/categories', ['category_name' => 'Frozen Goods'])->assertCreated()->json('category.category_id');
        $this->patchJson("/api/categories/{$id}", ['description' => 'Ice candy and ice cream'])->assertOk();
        $this->deleteJson("/api/categories/{$id}")->assertOk();

        $this->assertDatabaseMissing('categories', ['category_id' => $id]);
    }
}
