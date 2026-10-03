<?php

namespace Tests\Feature;

use App\Models\ApprovalStatus;
use App\Models\Category;
use App\Models\Store;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\RefreshesTestDatabase;
use Tests\TestCase;

/** Categories can be global (shared) or store-specific. Admins manage global categories, vendors manage their own. */
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

    public function test_a_vendor_can_add_describe_and_delete_their_own_category_but_not_global_or_others(): void
    {
        $vendor = $this->approvedVendor();
        Sanctum::actingAs($vendor);

        // Vendor creates their own category
        $id = $this->postJson('/api/categories', ['category_name' => 'Frozen Goods'])->assertCreated()->json('category.category_id');
        
        $this->assertDatabaseHas('categories', ['category_id' => $id, 'store_id' => $vendor->store->store_id]);

        // Vendor can edit their own
        $this->patchJson("/api/categories/{$id}", ['description' => 'Ice candy and ice cream'])->assertOk();

        // Create a global category
        $globalCat = Category::forceCreate(['category_name' => 'Global Snacks', 'description' => 'Global']);
        
        // Vendor cannot edit global
        $this->patchJson("/api/categories/{$globalCat->category_id}", ['description' => 'Changed'])->assertForbidden();
        // Vendor cannot delete global
        $this->deleteJson("/api/categories/{$globalCat->category_id}")->assertForbidden();

        // Create another vendor's category
        $otherVendor = $this->approvedVendor();
        $otherCat = Category::forceCreate(['category_name' => 'Other Snacks', 'store_id' => $otherVendor->store->store_id]);
        
        // Vendor cannot edit other vendor's category
        $this->patchJson("/api/categories/{$otherCat->category_id}", ['description' => 'Changed'])->assertForbidden();
        // Vendor cannot delete other vendor's category
        $this->deleteJson("/api/categories/{$otherCat->category_id}")->assertForbidden();

        // Vendor CAN delete their own
        $this->deleteJson("/api/categories/{$id}")->assertOk();
        $this->assertDatabaseMissing('categories', ['category_id' => $id]);
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
    public function test_an_admin_can_add_describe_and_delete_a_global_category_but_not_store_specific(): void
    {
        Sanctum::actingAs($this->makeUser('Admin'));

        $id = $this->postJson('/api/categories', ['category_name' => 'Frozen Goods'])->assertCreated()->json('category.category_id');
        $this->patchJson("/api/categories/{$id}", ['description' => 'Ice candy and ice cream'])->assertOk();
        $this->deleteJson("/api/categories/{$id}")->assertOk();

        $this->assertDatabaseMissing('categories', ['category_id' => $id]);

        $vendor = $this->approvedVendor();
        $storeCat = Category::forceCreate(['category_name' => 'Store Snacks', 'store_id' => $vendor->store->store_id]);
        
        $this->patchJson("/api/categories/{$storeCat->category_id}", ['description' => 'Changed'])->assertForbidden();
        $this->deleteJson("/api/categories/{$storeCat->category_id}")->assertForbidden();
    }
}
