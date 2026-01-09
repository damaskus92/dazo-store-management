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

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected $admin;

    protected $cashier;

    protected $superAdminToken;

    protected $adminToken;

    protected $cashierToken;

    protected $superAdminRole;

    protected $adminRole;

    protected $cashierRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat role hanya sekali untuk menghindari OverflowException
        $this->superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $this->adminRole = Role::firstOrCreate(['name' => 'admin']);
        $this->cashierRole = Role::firstOrCreate(['name' => 'cashier']);

        // Users
        $this->superAdmin = User::factory()->create([
            'role_id' => $this->superAdminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->superAdminToken = JWTAuth::fromUser($this->superAdmin);

        $this->admin = User::factory()->create([
            'role_id' => $this->adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->adminToken = JWTAuth::fromUser($this->admin);

        $this->cashier = User::factory()->create([
            'role_id' => $this->cashierRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->cashierToken = JWTAuth::fromUser($this->cashier);
    }

    #[Test]
    public function super_admin_can_list_users_with_pagination_and_search()
    {
        // Gunakan role yang sudah ada
        User::factory()->count(5)->create(['role_id' => $this->adminRole->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->superAdminToken)
            ->getJson('/api/users?per_page=2&search='.$this->superAdmin->first_name);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function super_admin_can_create_user()
    {
        $store = Store::factory()->create();

        $payload = [
            'store_id' => $store->id,
            'role_id' => $this->adminRole->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone_number' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->superAdminToken)
            ->postJson('/api/users', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => ['first_name' => 'John', 'email' => 'john@example.com'],
            ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }

    #[Test]
    public function super_admin_can_show_user()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->superAdminToken)
            ->getJson('/api/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'first_name', 'last_name', 'email', 'role', 'store'],
            ]);
    }

    #[Test]
    public function super_admin_can_update_user()
    {
        $user = User::factory()->create([
            'role_id' => $this->cashierRole->id,
            'first_name' => 'Original',
            'last_name' => 'User',
            'email' => 'original@example.com',
        ]);

        $payload = [
            'first_name' => 'Updated',
            'last_name' => $user->last_name,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'password' => 'newpass',
            'password_confirmation' => 'newpass',
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->superAdminToken)
            ->putJson('/api/users/'.$user->id, $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['first_name' => 'Updated'],
            ]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'first_name' => 'Updated']);
    }

    #[Test]
    public function super_admin_can_delete_user()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->superAdminToken)
            ->deleteJson('/api/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJson(['success' => true, 'message' => 'User has been successfully deleted.']);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function admin_cannot_create_update_or_delete_user()
    {
        $user = User::factory()->create(['role_id' => $this->cashierRole->id]);

        $payload = ['first_name' => 'Test', 'password' => 'password123', 'password_confirmation' => 'password123'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/users', $payload);
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->putJson('/api/users/'.$user->id, $payload);
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(403);
    }

    #[Test]
    public function cashier_cannot_create_update_or_delete_user()
    {
        $user = User::factory()->create(['role_id' => $this->adminRole->id]);

        $payload = ['first_name' => 'Test', 'password' => 'password123', 'password_confirmation' => 'password123'];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->postJson('/api/users', $payload);
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->putJson('/api/users/'.$user->id, $payload);
        $response->assertStatus(403);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->deleteJson('/api/users/'.$user->id);
        $response->assertStatus(403);
    }
}
