<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $otherAdmin;

    protected $cashier;

    protected $adminToken;

    protected $otherAdminToken;

    protected $cashierToken;

    protected $store;

    protected $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $cashierRole = Role::factory()->create(['name' => 'cashier']);

        // Stores
        $this->store = Store::factory()->create();
        $this->otherStore = Store::factory()->create();

        $this->admin = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->adminToken = JWTAuth::fromUser($this->admin);

        $this->otherAdmin = User::factory()->create([
            'store_id' => $this->otherStore->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->otherAdminToken = JWTAuth::fromUser($this->otherAdmin);

        $this->cashier = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->cashierToken = JWTAuth::fromUser($this->cashier);
    }

    #[Test]
    public function admin_can_list_products_of_own_store()
    {
        Product::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function admin_can_create_product()
    {
        $payload = [
            'name' => 'Product One',
            'sku' => 'SKU001',
            'price' => 100,
            'description' => 'Description here',
            'is_active' => true,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/products', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['name' => 'Product One', 'sku' => 'SKU001'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Product One',
            'store_id' => $this->store->id,
        ]);
    }

    #[Test]
    public function admin_can_show_product_of_own_store()
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'sku', 'price', 'description', 'is_active']]);
    }

    #[Test]
    public function admin_can_update_product_of_own_store()
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $payload = [
            'name' => 'Updated Product',
            'price' => 200,
            'is_active' => false,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/products/'.$product->id, $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['name' => 'Updated Product', 'price' => 200]]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Updated Product']);
    }

    #[Test]
    public function admin_can_delete_product_of_own_store()
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/products/'.$product->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Product successfully deleted.']);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    #[Test]
    public function admin_cannot_manage_products_of_other_store()
    {
        $product = Product::factory()->create(['store_id' => $this->otherStore->id]);

        $payload = ['name' => 'Hacked Product', 'price' => 100];

        // Show
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/products/'.$product->id);
        $response->assertStatus(403);

        // Update
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/products/'.$product->id, $payload);
        $response->assertStatus(403);

        // Delete
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/products/'.$product->id);
        $response->assertStatus(403);
    }

    #[Test]
    public function cashier_can_view_products_but_cannot_manage()
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        // Show (allowed)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->getJson('/api/products/'.$product->id);
        $response->assertStatus(200);

        // Create (not allowed)
        $payload = ['name' => 'New Product', 'price' => 100];
        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->postJson('/api/products', $payload);
        $response->assertStatus(403);

        // Update (not allowed)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->putJson('/api/products/'.$product->id, $payload);
        $response->assertStatus(403);

        // Delete (not allowed)
        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->deleteJson('/api/products/'.$product->id);
        $response->assertStatus(403);
    }
}
