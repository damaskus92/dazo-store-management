<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected $admin;

    protected $cashier;

    protected $jwtToken;

    protected $adminToken;

    protected $cashierToken;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $cashierRole = Role::factory()->create(['name' => 'cashier']);

        // Super Admin
        $this->superAdmin = User::factory()->create([
            'role_id' => $superAdminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->jwtToken = JWTAuth::fromUser($this->superAdmin);

        // Admin
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->adminToken = JWTAuth::fromUser($this->admin);

        // Cashier
        $this->cashier = User::factory()->create([
            'role_id' => $cashierRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->cashierToken = JWTAuth::fromUser($this->cashier);
    }

    #[Test]
    public function super_admin_can_list_stores_with_pagination_and_search()
    {
        Store::factory()->create(['name' => 'Central Store', 'level' => 'center']);
        Store::factory()->create(['name' => 'Branch Store', 'level' => 'branch']);
        Store::factory()->create(['name' => 'Retail Store', 'level' => 'retail']);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->getJson('/api/stores');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['data', 'current_page', 'last_page', 'total']]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->getJson('/api/stores?search=Central');

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Central Store'])
            ->assertJsonCount(1, 'data.data');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->getJson('/api/stores?search=branch');

        $response->assertStatus(200)
            ->assertJsonFragment(['level' => 'branch'])
            ->assertJsonCount(1, 'data.data');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->getJson('/api/stores?per_page=2');

        $response->assertStatus(200)
            ->assertJson(['data' => ['per_page' => 2]]);
    }

    #[Test]
    public function super_admin_can_create_store_and_auto_generate_users()
    {
        $payload = [
            'name' => 'Central Store',
            'level' => 'center',
            'address' => '123 Main St',
            'phone' => '08123456789',
            'parent_id' => null,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->postJson('/api/stores', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Central Store',
                    'level' => 'center',
                ],
            ]);

        $storeId = $response->json('data.id');
        $this->assertDatabaseHas('stores', ['id' => $storeId]);
        $this->assertDatabaseHas('users', ['store_id' => $storeId, 'first_name' => 'Admin']);
        $this->assertDatabaseHas('users', ['store_id' => $storeId, 'first_name' => 'Cashier']);
    }

    #[Test]
    public function super_admin_can_show_store()
    {
        $store = Store::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->getJson("/api/stores/{$store->id}");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'name', 'level', 'parent_id', 'users']]);
    }

    #[Test]
    public function super_admin_can_update_store()
    {
        $store = Store::factory()->create(['name' => 'Old Store']);

        $payload = ['name' => 'Updated Store', 'level' => 'branch'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->putJson("/api/stores/{$store->id}", $payload);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'data' => ['name' => 'Updated Store', 'level' => 'branch']]);

        $this->assertDatabaseHas('stores', ['id' => $store->id, 'name' => 'Updated Store']);
    }

    #[Test]
    public function super_admin_cannot_set_parent_as_self()
    {
        $store = Store::factory()->create();

        $payload = ['parent_id' => $store->id];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->putJson("/api/stores/{$store->id}", $payload);

        $response->assertStatus(422)
            ->assertJson(['success' => false, 'message' => 'Parent store cannot be itself.']);
    }

    #[Test]
    public function super_admin_can_delete_store()
    {
        $store = Store::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->jwtToken)
            ->deleteJson("/api/stores/{$store->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'Store has been successfully deleted.']);

        $this->assertDatabaseMissing('stores', ['id' => $store->id]);
    }

    #[Test]
    public function admin_cannot_create_store()
    {
        $payload = ['name' => 'Store', 'level' => 'center'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/stores', $payload);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    #[Test]
    public function cashier_cannot_create_store()
    {
        $payload = ['name' => 'Store', 'level' => 'center'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->postJson('/api/stores', $payload);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    #[Test]
    public function admin_cannot_update_store()
    {
        $store = Store::factory()->create();

        $payload = ['name' => 'Attempt Update'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson("/api/stores/{$store->id}", $payload);

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    #[Test]
    public function cashier_cannot_delete_store()
    {
        $store = Store::factory()->create();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->deleteJson("/api/stores/{$store->id}");

        $response->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }
}
