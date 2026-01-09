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

class CashierTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $otherAdmin;

    protected $adminToken;

    protected $otherAdminToken;

    protected $store;

    protected $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        Role::factory()->create(['name' => 'cashier']);

        // Stores
        $this->store = Store::factory()->create();
        $this->otherStore = Store::factory()->create();

        // Admin for own store
        $this->admin = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->adminToken = JWTAuth::fromUser($this->admin);

        // Admin for other store
        $this->otherAdmin = User::factory()->create([
            'store_id' => $this->otherStore->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->otherAdminToken = JWTAuth::fromUser($this->otherAdmin);
    }

    #[Test]
    public function admin_can_list_cashiers_of_own_store()
    {
        $cashierRole = Role::where('name', 'cashier')->first();

        User::factory()->count(5)->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/cashiers');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data',
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ])
            ->assertJsonCount(5, 'data.data'); // pastikan 5 cashier muncul
    }

    #[Test]
    public function admin_can_create_cashier()
    {
        $payload = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'johndoe@store.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'phone_number' => '081234567890',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/cashiers', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier has been successfully created.',
            ])
            ->assertJsonPath('data.first_name', 'John')
            ->assertJsonPath('data.email', 'johndoe@store.com');

        $this->assertDatabaseHas('users', [
            'email' => 'johndoe@store.com',
            'store_id' => $this->store->id,
        ]);
    }

    #[Test]
    public function admin_can_show_cashier_of_own_store()
    {
        $cashierRole = Role::where('name', 'cashier')->first();
        $cashier = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
            'first_name' => 'Alice',
            'email' => 'alice@store.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/cashiers/'.$cashier->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone_number',
                    'role' => ['id', 'name'],
                    'store' => ['id', 'name'],
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.first_name', 'Alice')
            ->assertJsonPath('data.email', 'alice@store.com')
            ->assertJsonPath('data.role.name', 'cashier');
    }

    #[Test]
    public function admin_can_update_cashier_of_own_store()
    {
        $cashierRole = Role::where('name', 'cashier')->first();
        $cashier = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
            'first_name' => 'OldName',
            'email' => 'old@email.com',
        ]);

        $payload = [
            'first_name' => 'NewName',
            'email' => 'new@email.com',
            'phone_number' => '089999999999',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/cashiers/'.$cashier->id, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier has been successfully updated.',
            ])
            ->assertJsonPath('data.first_name', 'NewName')
            ->assertJsonPath('data.email', 'new@email.com')
            ->assertJsonPath('data.phone_number', '089999999999');

        $this->assertDatabaseHas('users', [
            'id' => $cashier->id,
            'first_name' => 'NewName',
            'email' => 'new@email.com',
        ]);
    }

    #[Test]
    public function admin_can_delete_cashier_of_own_store()
    {
        $cashierRole = Role::where('name', 'cashier')->first();
        $cashier = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/cashiers/'.$cashier->id);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Cashier has been successfully deleted.',
            ]);

        $this->assertDatabaseMissing('users', ['id' => $cashier->id]);
    }

    #[Test]
    public function admin_cannot_manage_cashiers_of_other_store()
    {
        $cashierRole = Role::where('name', 'cashier')->first();
        $otherCashier = User::factory()->create([
            'store_id' => $this->otherStore->id,
            'role_id' => $cashierRole->id,
        ]);

        $payload = ['first_name' => 'Trying to hack'];

        // Show → 403
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/cashiers/'.$otherCashier->id);
        $response->assertStatus(403);

        // Update → 403
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/cashiers/'.$otherCashier->id, $payload);
        $response->assertStatus(403);

        // Delete → 403
        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/cashiers/'.$otherCashier->id);
        $response->assertStatus(403);

        // Pastikan data tidak berubah
        $this->assertDatabaseHas('users', [
            'id' => $otherCashier->id,
            'store_id' => $this->otherStore->id,
        ]);
    }
}
