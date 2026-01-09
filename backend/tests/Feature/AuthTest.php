<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function createUserWithRole(string $roleName): User
    {
        $role = Role::factory()->create([
            'name' => $roleName,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'password' => bcrypt('password'),
        ]);
    }

    #[Test]
    public function super_admin_can_login_and_access_me()
    {
        $user = $this->createUserWithRole('super_admin');

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $login->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment([
                'email' => $user->email,
            ]);
    }

    #[Test]
    public function admin_can_login_and_access_me()
    {
        $user = $this->createUserWithRole('admin');

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk();
    }

    #[Test]
    public function cashier_can_login_and_access_me()
    {
        $user = $this->createUserWithRole('cashier');

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/me')
            ->assertOk();
    }

    #[Test]
    public function user_cannot_login_with_invalid_credentials()
    {
        $this->postJson('/api/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong',
        ])->assertStatus(401);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_me()
    {
        $this->getJson('/api/me')
            ->assertStatus(401);
    }

    #[Test]
    public function user_can_logout()
    {
        $user = $this->createUserWithRole('admin');

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $token = $login->json('access_token');

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJson([
                'message' => 'Successfully logged out',
            ]);
    }
}
