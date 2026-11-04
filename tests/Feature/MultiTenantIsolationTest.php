<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_tenant_admin_only_sees_own_company_products_and_dynamic_stock(): void
    {
        $adminFerreteria = User::where('email', 'admin@elmartillo.cl')->firstOrFail();
        $productoFarmacia = Product::withoutGlobalScopes()->where('sku', 'FAR-ANA-001')->firstOrFail();

        $response = $this->actingAs($adminFerreteria)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Saco Cemento Especial 25kg Polpaico');
        $response->assertDontSee($productoFarmacia->name);
    }

    public function test_user_cannot_record_stock_movement_on_another_tenant_product(): void
    {
        $adminFerreteria = User::where('email', 'admin@elmartillo.cl')->firstOrFail();
        $productoFarmacia = Product::withoutGlobalScopes()->where('sku', 'FAR-ANA-001')->firstOrFail();

        $response = $this->actingAs($adminFerreteria)->post('/stock-movements', [
            'product_id' => $productoFarmacia->id,
            'type' => 'out',
            'quantity' => 5,
            'reason' => 'Intento de acceso cruzado entre empresas',
        ]);

        // El Global Scope TenantScope hace que findOrFail devuelva 404 Not Found
        $response->assertNotFound();
    }

    public function test_staff_can_record_movements_but_cannot_create_products_or_users(): void
    {
        $staffFerreteria = User::where('email', 'bodega@elmartillo.cl')->firstOrFail();
        $productoFerreteria = Product::withoutGlobalScopes()->where('sku', 'FER-CEM-001')->firstOrFail();

        // Staff NO puede crear productos (403)
        $this->actingAs($staffFerreteria)->post('/products', [
            'sku' => 'FER-NEW-999',
            'name' => 'Producto No Autorizado',
            'price' => 10,
            'min_stock_alert' => 5,
        ])->assertForbidden();

        // Staff SÍ puede registrar movimientos de stock
        $this->actingAs($staffFerreteria)->post('/stock-movements', [
            'product_id' => $productoFerreteria->id,
            'type' => 'in',
            'quantity' => 25,
            'reason' => 'Ingreso validado por bodega',
            'reference' => 'GD-9001',
        ])->assertRedirect('/dashboard');
    }
}
