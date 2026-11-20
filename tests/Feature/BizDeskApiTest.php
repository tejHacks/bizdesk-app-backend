<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BizDeskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['business_name' => 'Ada Store']);
        Sanctum::actingAs($this->owner);
    }

    private function product(array $attrs = []): Product
    {
        return Product::factory()->for($this->owner, 'owner')->create([
            'price' => 30000, 'cost' => 18000, 'stock' => 10, 'low_stock_at' => 3, ...$attrs,
        ]);
    }

    public function test_register_and_login_return_tokens(): void
    {
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Tunde', 'business_name' => 'Tunde Ventures', 'email' => 'tunde@example.com',
            'password' => 'password123', 'device_name' => 'iphone',
        ])->assertCreated()->assertJsonPath('user.business_name', 'Tunde Ventures')->assertJsonStructure(['token']);

        $this->postJson('/api/v1/auth/login', ['email' => 'tunde@example.com', 'password' => 'password123', 'device_name' => 'x'])
            ->assertOk()->assertJsonStructure(['token']);
    }

    public function test_guests_are_rejected(): void
    {
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/v1/products')->assertUnauthorized();
    }

    public function test_product_crud_and_restock(): void
    {
        $id = $this->postJson('/api/v1/products', ['name' => 'Water', 'sku' => 'W1', 'price' => 30000, 'cost' => 18000, 'stock' => 5])
            ->assertCreated()->json('data.id');

        $this->patchJson("/api/v1/products/{$id}", ['price' => 35000])->assertOk()->assertJsonPath('data.price', 35000);
        $this->postJson("/api/v1/products/{$id}/restock", ['qty' => 20])->assertOk()->assertJsonPath('data.stock', 25);
        $this->getJson('/api/v1/products?search=wat')->assertOk()->assertJsonPath('total', 1);
        $this->deleteJson("/api/v1/products/{$id}")->assertNoContent();
    }

    public function test_sku_is_unique_per_business_only(): void
    {
        $this->product(['sku' => 'DUP']);
        $this->postJson('/api/v1/products', ['name' => 'X', 'sku' => 'DUP', 'price' => 100, 'stock' => 1])
            ->assertUnprocessable()->assertJsonValidationErrors('sku');

        Product::factory()->create(['sku' => 'OTHER']);   // another business
        $this->postJson('/api/v1/products', ['name' => 'Y', 'sku' => 'OTHER', 'price' => 100, 'stock' => 1])->assertCreated();
    }

    public function test_businesses_cannot_see_each_others_data(): void
    {
        $foreign = Product::factory()->create();                 // belongs to another user
        $foreignCustomer = Customer::factory()->create();

        $this->getJson("/api/v1/products/{$foreign->id}")->assertNotFound();
        $this->patchJson("/api/v1/products/{$foreign->id}", ['price' => 1])->assertNotFound();
        $this->getJson("/api/v1/customers/{$foreignCustomer->id}")->assertNotFound();
        $this->getJson('/api/v1/products')->assertJsonPath('total', 0);

        // Can't sell someone else's product either.
        $this->postJson('/api/v1/sales', ['items' => [['product_id' => $foreign->id, 'qty' => 1]]])
            ->assertUnprocessable()->assertJsonValidationErrors('items.0.product_id');
    }

    public function test_a_sale_deducts_stock_and_computes_totals(): void
    {
        $water = $this->product();
        $pie = $this->product(['price' => 80000, 'cost' => 50000, 'stock' => 4]);
        $customer = Customer::factory()->for($this->owner)->create();

        $sale = $this->postJson('/api/v1/sales', [
            'items' => [
                ['product_id' => $water->id, 'qty' => 2],
                ['product_id' => $pie->id, 'qty' => 1],
                ['product_id' => $water->id, 'qty' => 1],              // duplicate lines are merged
            ],
            'customer_id' => $customer->id,
            'discount' => 10000,
            'payment_method' => 'transfer',
        ])->assertCreated();

        $sale->assertJsonPath('data.subtotal', 170000)
            ->assertJsonPath('data.total', 160000)
            ->assertJsonPath('data.cost_total', 104000)
            ->assertJsonPath('data.profit', 56000)
            ->assertJsonPath('data.receipt_no', 'INV-00001')
            ->assertJsonCount(2, 'data.items');

        $this->assertSame(7, $water->fresh()->stock);
        $this->assertSame(3, $pie->fresh()->stock);
    }

    public function test_overselling_is_refused_and_nothing_changes(): void
    {
        $water = $this->product(['stock' => 2]);
        $pie = $this->product(['stock' => 5]);

        $this->postJson('/api/v1/sales', ['items' => [
            ['product_id' => $pie->id, 'qty' => 1],
            ['product_id' => $water->id, 'qty' => 3],
        ]])->assertStatus(409)->assertJson(['code' => 'insufficient_stock', 'available' => 2]);

        $this->assertSame(5, $pie->fresh()->stock, 'transaction must roll back the first line');
        $this->assertDatabaseCount('sales', 0);
    }

    public function test_voiding_a_sale_restores_stock(): void
    {
        $water = $this->product();
        $id = $this->postJson('/api/v1/sales', ['items' => [['product_id' => $water->id, 'qty' => 4]]])->json('data.id');
        $this->assertSame(6, $water->fresh()->stock);

        $this->deleteJson("/api/v1/sales/{$id}")->assertNoContent();
        $this->assertSame(10, $water->fresh()->stock);
        $this->assertDatabaseCount('sale_items', 0);
    }

    public function test_receipt_numbers_are_per_business(): void
    {
        $water = $this->product();
        $this->postJson('/api/v1/sales', ['items' => [['product_id' => $water->id, 'qty' => 1]]]);
        $this->postJson('/api/v1/sales', ['items' => [['product_id' => $water->id, 'qty' => 1]]])
            ->assertJsonPath('data.number', 2);
    }

    public function test_report_summary(): void
    {
        $water = $this->product();
        $this->postJson('/api/v1/sales', ['items' => [['product_id' => $water->id, 'qty' => 9]]]);
        $this->postJson('/api/v1/expenses', ['category' => 'Rent', 'amount' => 50000])->assertCreated();

        $this->getJson('/api/v1/reports/summary?period=today')
            ->assertOk()
            ->assertJsonPath('data.sales_count', 1)
            ->assertJsonPath('data.revenue', 270000)
            ->assertJsonPath('data.gross_profit', 108000)
            ->assertJsonPath('data.net_profit', 58000)
            ->assertJsonPath('data.top_products.0.qty', 9)
            ->assertJsonPath('data.low_stock.0.stock', 1)
            ->assertJsonCount(7, 'data.last_7_days');
    }

    public function test_expense_validation(): void
    {
        $this->postJson('/api/v1/expenses', ['category' => 'Holiday', 'amount' => 0])
            ->assertUnprocessable()->assertJsonValidationErrors(['category', 'amount']);
    }

    public function test_web_dashboard_login(): void
    {
        // setUp() made Sanctum the default guard; the web dashboard uses the session guard.
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');
        $user = User::factory()->create(['email' => 'boss@example.com', 'password' => 'password', 'business_name' => 'Boss Shop']);

        $this->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => 'boss@example.com', 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Boss Shop')->assertSee('Net profit');
    }

    public function test_seeder_builds_a_consistent_demo_business(): void
    {
        $this->seed();
        $owner = User::where('email', 'owner@bizdesk.test')->first();

        $this->assertSame(7, $owner->sales()->count());
        $this->assertSame((int) $owner->sales()->sum('total'), (int) SaleItem::sum('total'));
    }
}
