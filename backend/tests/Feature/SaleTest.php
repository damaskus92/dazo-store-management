<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $cashier;

    protected $adminToken;

    protected $cashierToken;

    protected $store;

    protected $products;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $cashierRole = Role::factory()->create(['name' => 'cashier']);

        // Store
        $this->store = Store::factory()->create();

        // Admin
        $this->admin = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $adminRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->adminToken = JWTAuth::fromUser($this->admin);

        // Cashier
        $this->cashier = User::factory()->create([
            'store_id' => $this->store->id,
            'role_id' => $cashierRole->id,
            'password' => Hash::make('password123'),
        ]);
        $this->cashierToken = JWTAuth::fromUser($this->cashier);

        // Products
        $this->products = Product::factory()->count(3)->create(['store_id' => $this->store->id]);
    }

    #[Test]
    public function cashier_can_create_sale()
    {
        $payload = [
            'items' => $this->products->map(function ($p) {
                return [
                    'product_id' => $p->id,
                    'product_name' => $p->name,
                    'price' => 100,
                    'quantity' => 2,
                ];
            })->toArray(),
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->postJson('/api/sales', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['id', 'transaction_number', 'items'], 'message']);

        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 3);
    }

    #[Test]
    public function cashier_can_pay_sale()
    {
        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
            'total_amount' => 500,
            'paid_amount' => 0,
            'change_amount' => 0,
        ]);

        $payload = [
            'sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 600,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->postJson('/api/payments', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'paid_amount', 'change_amount'], 'message']);

        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'amount' => 600,
        ]);

        $sale->refresh();
        $this->assertEquals(600, $sale->paid_amount);
        $this->assertEquals(100, $sale->change_amount);
    }

    #[Test]
    public function admin_cannot_pay_sale()
    {
        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
            'total_amount' => 500,
        ]);

        $payload = [
            'sale_id' => $sale->id,
            'method' => 'cash',
            'amount' => 500,
        ];

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->postJson('/api/payments', $payload);

        $response->assertStatus(403);
    }

    #[Test]
    public function admin_can_view_sales_list()
    {
        Sale::factory()->count(5)->create(['store_id' => $this->store->id, 'cashier_id' => $this->cashier->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function cashier_can_view_sales_list()
    {
        Sale::factory()->count(5)->create(['store_id' => $this->store->id, 'cashier_id' => $this->cashier->id]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->getJson('/api/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['data', 'current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    #[Test]
    public function admin_can_view_sale_detail()
    {
        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->adminToken)
            ->getJson('/api/sales/'.$sale->id);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'transaction_number', 'items', 'payments']]);
    }

    #[Test]
    public function cashier_can_view_sale_detail()
    {
        $sale = Sale::factory()->create([
            'store_id' => $this->store->id,
            'cashier_id' => $this->cashier->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->cashierToken)
            ->getJson('/api/sales/'.$sale->id);

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['id', 'transaction_number', 'items', 'payments']]);
    }
}
