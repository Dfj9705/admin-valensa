<?php

namespace Tests\Feature;

use App\Filament\Resources\CategoriaGastoResource;
use App\Filament\Resources\ClienteResource;
use App\Filament\Resources\EgresoServicioResource;
use App\Filament\Resources\EmisorResource;
use App\Filament\Resources\GastoResource;
use App\Filament\Resources\IngresoServicioResource;
use App\Filament\Resources\ProductoResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\VentaResource;
use App\Models\Producto;
use App\Models\ProductoMovimiento;
use App\Models\User;
use App\Models\Venta;
use App\Services\Sales\ConfirmVenta;
use App\Services\Sales\ManagePago;
use App\Services\Tekra\TekraFelService;
use App\Support\Access;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuthorizationIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never migrate, truncate or seed the configured business database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array', 'services.valensa.admin_email' => null]);
        DB::purge('sqlite');
        self::assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (glob(database_path('migrations/*.php')) as $file) {
            if (str_contains($file, 'settings') || str_contains($file, 'delete_null_field')) {
                continue;
            }
            (require $file)->up();
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function login(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function product(int $stock = 10): Producto
    {
        $product = Producto::create(['pro_nombre' => 'Equipo', 'pro_precio_costo' => 5, 'pro_precio_venta_min' => 10, 'pro_precio_venta_max' => 20, 'pro_activo' => true]);
        $product->movimientos()->create(['mop_tipo' => 'entrada', 'mop_cantidad' => $stock, 'mop_fecha' => now()]);

        return $product;
    }

    private function sale(Producto $product, int $quantity = 2): Venta
    {
        $sale = Venta::create(['ven_estado' => 'draft', 'ven_total' => 100]);
        $sale->productos()->create(['pro_id' => $product->getKey(), 'qty' => $quantity, 'unit_price' => 10, 'discount' => 0, 'line_total' => $quantity * 10, 'description_snapshot' => 'Equipo', 'uom_snapshot' => 'UNI']);

        return $sale;
    }

    public function test_guest_is_denied_all_resources(): void
    {
        foreach ([VentaResource::class, ProductoResource::class, ClienteResource::class, GastoResource::class, CategoriaGastoResource::class, IngresoServicioResource::class, EgresoServicioResource::class, EmisorResource::class, UserResource::class, RoleResource::class] as $resource) {
            self::assertFalse($resource::canViewAny());
            self::assertFalse($resource::canCreate());
            self::assertFalse($resource::canDeleteAny());
        }
    }

    public function test_seller_has_operational_access_without_deletion_or_administration(): void
    {
        $user = $this->login('vendedor');
        foreach ([VentaResource::class, ProductoResource::class, ClienteResource::class] as $resource) {
            self::assertTrue($resource::canViewAny());
            self::assertTrue($resource::canCreate());
            self::assertFalse($resource::canDeleteAny());
        }
        foreach ([GastoResource::class, CategoriaGastoResource::class, IngresoServicioResource::class, EgresoServicioResource::class, EmisorResource::class, UserResource::class, RoleResource::class] as $resource) {
            self::assertFalse($resource::canViewAny());
        }
        $user->givePermissionTo(['ventas.eliminar', 'usuarios.ver', 'gastos.ver']);
        self::assertFalse($user->can('ventas.eliminar'));
        self::assertFalse($user->can('usuarios.ver'));
        self::assertFalse(GastoResource::canViewAny());
        self::assertTrue(Access::allows('pagos.crear'));
        self::assertTrue(Access::allows('inventario.crear'));
    }

    public function test_administrator_has_business_resources_but_no_users_or_roles(): void
    {
        $this->login('administrador');
        foreach ([VentaResource::class, ProductoResource::class, ClienteResource::class, GastoResource::class, CategoriaGastoResource::class, IngresoServicioResource::class, EgresoServicioResource::class, EmisorResource::class] as $resource) {
            self::assertTrue($resource::canViewAny());
            self::assertTrue($resource::canCreate());
        }
        self::assertFalse(UserResource::canViewAny());
        self::assertFalse(RoleResource::canViewAny());
    }

    public function test_revoked_module_permission_is_respected(): void
    {
        $user = $this->login('vendedor');
        $user->roles->first()->revokePermissionTo('productos.crear');
        $user->unsetRelation('roles');
        self::assertFalse(ProductoResource::canCreate());
        self::assertTrue(ProductoResource::canViewAny());
    }

    public function test_superadministrator_cannot_delete_certified_sale_or_history(): void
    {
        $this->login('superadministrador');
        self::assertTrue(UserResource::canViewAny());
        $sale = $this->sale($this->product());
        $sale->update(['ven_estado' => 'certified', 'ven_fel_uuid' => 'UUID']);
        self::assertFalse(VentaResource::canDelete($sale));
        $this->expectException(ValidationException::class);
        $sale->delete();
    }

    public function test_stock_is_dynamic_and_excess_outbound_is_rolled_back(): void
    {
        $product = $this->product(10);
        $product->movimientos()->create(['mop_tipo' => 'salida', 'mop_cantidad' => 7, 'mop_fecha' => now()]);
        self::assertSame(3, $product->fresh()->pro_stock);
        try {
            $product->movimientos()->create(['mop_tipo' => 'salida', 'mop_cantidad' => 4, 'mop_fecha' => now()]);
            self::fail('Overdraw allowed');
        } catch (ValidationException $e) {
            self::assertSame(2, $product->movimientos()->count());
            self::assertSame(3, $product->fresh()->pro_stock);
        }
    }

    public function test_movement_cannot_be_edited(): void
    {
        $movement = $this->product()->movimientos()->first();
        $this->expectException(ValidationException::class);
        $movement->update(['mop_cantidad' => 999]);
    }

    public function test_movement_cannot_be_deleted(): void
    {
        $movement = $this->product()->movimientos()->first();
        $this->expectException(ValidationException::class);
        $movement->delete();
    }

    public function test_product_with_history_cannot_be_deleted(): void
    {
        $this->login('superadministrador');
        $product = $this->product();
        self::assertFalse(ProductoResource::canDelete($product));
        $this->expectException(ValidationException::class);
        $product->delete();
    }

    public function test_confirmation_and_cancellation_are_not_repeatable(): void
    {
        $user = $this->login('vendedor');
        $product = $this->product();
        $sale = $this->sale($product);
        $staleSale = $sale->fresh();
        $service = app(ConfirmVenta::class);
        $service->handle($sale, $user->id);
        self::assertSame(8, $product->fresh()->pro_stock);
        try {
            $service->handle($staleSale, $user->id);
            self::fail('Repeated confirmation');
        } catch (ValidationException $e) {
        }
        $service->handleAnulation($staleSale, $user->id);
        self::assertSame(10, $product->fresh()->pro_stock);
        try {
            $service->handleAnulation($staleSale, $user->id);
            self::fail('Repeated return');
        } catch (ValidationException $e) {
        }
        self::assertSame(10, $product->fresh()->pro_stock);
    }

    public function test_duplicate_product_lines_cannot_overdraw_and_sale_rolls_back(): void
    {
        $user = $this->login('vendedor');
        $product = $this->product(3);
        $sale = $this->sale($product, 2);
        $sale->productos()->create($sale->productos()->first()->only(['pro_id', 'qty', 'unit_price', 'discount', 'line_total', 'description_snapshot', 'uom_snapshot']));
        try {
            app(ConfirmVenta::class)->handle($sale, $user->id);
            self::fail('Overdraw allowed');
        } catch (ValidationException $e) {
        }
        self::assertSame(3, $product->fresh()->pro_stock);
        self::assertSame('draft', $sale->fresh()->ven_estado);
        self::assertSame(1, ProductoMovimiento::count());
    }

    public function test_confirmation_service_rejects_unauthorized_caller(): void
    {
        $sale = $this->sale($this->product());
        $this->expectException(AuthorizationException::class);
        app(ConfirmVenta::class)->handle($sale, 1);
    }

    public function test_payments_validate_create_and_edit_balances(): void
    {
        $this->login('vendedor');
        $sale = $this->sale($this->product());
        $service = app(ManagePago::class);
        $payment = $service->save($sale, ['vpa_monto' => 60, 'vpa_fecha' => now()]);
        $second = $service->save($sale, ['vpa_monto' => 40, 'vpa_fecha' => now()]);
        try {
            $service->save($sale, ['vpa_monto' => 61], $payment);
            self::fail('Excess edit allowed');
        } catch (ValidationException $e) {
        }
        self::assertEquals(100, $sale->pagos()->sum('vpa_monto'));
        $service->save($sale, ['vpa_monto' => 50], $payment);
        self::assertEquals(90, $sale->pagos()->sum('vpa_monto'));
        $this->expectException(AuthorizationException::class);
        $service->delete($second);
    }

    public function test_negative_payment_is_rejected(): void
    {
        $this->login('vendedor');
        $sale = $this->sale($this->product());
        $this->expectException(ValidationException::class);
        app(ManagePago::class)->save($sale, ['vpa_monto' => -1, 'vpa_fecha' => now()]);
    }

    public function test_cancelled_sale_rejects_payments(): void
    {
        $this->login('administrador');
        $sale = $this->sale($this->product());
        $sale->update(['ven_estado' => 'cancelled']);
        $this->expectException(ValidationException::class);
        app(ManagePago::class)->save($sale, ['vpa_monto' => 1, 'vpa_fecha' => now()]);
    }

    public function test_report_routes_deny_seller(): void
    {
        $this->login('vendedor');
        foreach (['products.print', 'reportes.servicios', 'reportes.caja', 'reportes.ventas.pendientes', 'catalogo.print', 'reportes.deudores'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_fel_service_authorizes_before_contacting_tekra(): void
    {
        $sale = $this->sale($this->product());
        $this->expectException(AuthorizationException::class);
        app(TekraFelService::class)->certificarFactura($sale);
    }

    public function test_livewire_denies_direct_mount_of_restricted_pages(): void
    {
        $this->login('vendedor');
        foreach ([\App\Filament\Resources\GastoResource\Pages\ListGastos::class,
            \App\Filament\Resources\UserResource\Pages\ListUsers::class,
            \App\Filament\Pages\ReportadorFinanciero::class,
            \TomatoPHP\FilamentPWA\Filament\Pages\PWASettingsPage::class] as $page) {
            \Livewire\Livewire::test($page)->assertForbidden();
        }
    }

    public function test_inline_category_creation_checks_permission_in_action_and_callback(): void
    {
        $user = $this->login('administrador');
        $user->roles->first()->revokePermissionTo('categorias_gastos.crear');
        $user->unsetRelation('roles');
        $page = new \App\Filament\Resources\GastoResource\Pages\CreateGasto;
        $form = GastoResource::form(\Filament\Forms\Form::make($page)->model(\App\Models\Gasto::class));
        $select = collect($form->getFlatComponents())->first(fn ($component) => $component instanceof \Filament\Forms\Components\Select && $component->getName() === 'cat_id');
        self::assertFalse($select->getCreateOptionAction()->isAuthorized());
        $this->expectException(AuthorizationException::class);
        $select->evaluate($select->getCreateOptionUsing(), ['data' => ['cat_nombre' => 'No permitido', 'cat_activo' => true]]);
    }

    public function test_inline_client_creation_cannot_bypass_revoked_permission(): void
    {
        $user = $this->login('vendedor');
        $user->roles->first()->revokePermissionTo('clientes.crear');
        $user->unsetRelation('roles');
        $page = new \App\Filament\Resources\VentaResource\Pages\CreateVenta;
        $form = VentaResource::form(\Filament\Forms\Form::make($page)->model(Venta::class));
        $select = collect($form->getFlatComponents())->first(fn ($component) => $component instanceof \Filament\Forms\Components\Select && $component->getName() === 'ven_cliente_id');
        self::assertFalse($select->getCreateOptionAction()->isAuthorized());
        $this->expectException(AuthorizationException::class);
        $select->evaluate($select->getCreateOptionUsing(), ['data' => ['cli_nit' => 'CF', 'cli_nombre' => 'No permitido']]);
    }

    public function test_custom_sale_actions_enforce_their_own_permissions(): void
    {
        $user = $this->login('vendedor');
        $user->roles->first()->revokePermissionTo(['ventas.confirmar', 'ventas.certificar', 'ventas.anular']);
        $user->unsetRelation('roles');
        $page = new \App\Filament\Resources\VentaResource\Pages\EditVenta;
        $page->record = $this->sale($this->product());
        $method = new \ReflectionMethod($page, 'getHeaderActions');
        $actions = collect($method->invoke($page))->keyBy(fn ($action) => $action->getName());
        foreach (['confirm', 'certify', 'anulation'] as $name) {
            self::assertFalse($actions[$name]->isAuthorized());
        }
        self::assertTrue($actions['cotizacion']->isAuthorized());
    }

    public function test_certified_sale_cannot_be_saved_through_the_edit_page(): void
    {
        $this->login('superadministrador');
        $sale = $this->sale($this->product());
        $sale->update(['ven_estado' => 'certified', 'ven_fel_uuid' => 'UUID']);
        $page = new \App\Filament\Resources\VentaResource\Pages\EditVenta;
        $page->record = $sale;
        $method = new \ReflectionMethod($page, 'beforeSave');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $method->invoke($page);
    }

    public function test_quotation_pdf_is_stored_privately_and_downloaded(): void
    {
        $this->login('vendedor');
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::fake('public');
        $sale = $this->sale($this->product());
        $client = \App\Models\Cliente::create(['cli_nombre' => 'Cliente de prueba', 'cli_nit' => 'CF']);
        $sale->update(['ven_cliente_id' => $client->getKey()]);
        $page = new \App\Filament\Resources\VentaResource\Pages\EditVenta;
        $page->record = $sale;
        $actions = collect((new \ReflectionMethod($page, 'getHeaderActions'))->invoke($page))
            ->keyBy(fn ($action) => $action->getName());
        $response = $actions['cotizacion']->call();
        self::assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $path = "cotizaciones/cotizacion_{$sale->ven_id}_{$sale->created_at->format('Ymd')}.pdf";
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($path);
        self::assertStringStartsWith('%PDF', \Illuminate\Support\Facades\Storage::disk('local')->get($path));
    }

    public function test_fel_certification_action_persists_a_mocked_success(): void
    {
        $user = $this->login('vendedor');
        $sale = $this->sale($this->product());
        $sale = app(ConfirmVenta::class)->handle($sale, $user->id);
        $client = \App\Models\Cliente::create(['cli_nombre' => 'Cliente FEL', 'cli_nit' => 'CF']);
        $sale->update(['ven_cliente_id' => $client->getKey()]);
        $raw = (object) ['NumeroAutorizacion' => 'TEST-UUID', 'SerieDocumento' => 'TEST', 'NumeroDocumento' => '1',
            'FechaHoraCertificacion' => '2026-11-05T12:00:00-06:00', 'NITCertificador' => 'TEST',
            'NombreCertificador' => 'Certificador de prueba', 'EstadoDocumento' => 'Vigente', 'FechaHoraEmision' => '2026-11-05T12:00:00-06:00'];
        $this->mock(TekraFelService::class, function ($mock) use ($raw) {
            $mock->shouldReceive('certificarFactura')->once()->andReturn(['raw' => $raw, 'resultado' => '{"error":0}', 'pdf_base64' => '', 'qr' => 'TEST']);
        });
        $page = new class extends \App\Filament\Resources\VentaResource\Pages\EditVenta
        {
            public function refreshFormData(array $attributes): void {}
        };
        $page->record = $sale;
        $actions = collect((new \ReflectionMethod($page, 'getHeaderActions'))->invoke($page))->keyBy(fn ($action) => $action->getName());
        $actions['certify']->call();
        self::assertSame('certified', $sale->fresh()->ven_estado);
        self::assertSame('TEST-UUID', $sale->fresh()->ven_fel_uuid);
    }

    public function test_fel_cancellation_returns_stock_once_after_mocked_remote_success(): void
    {
        $user = $this->login('vendedor');
        $product = $this->product();
        $sale = app(ConfirmVenta::class)->handle($this->sale($product), $user->id);
        $sale->update(['ven_estado' => 'certified', 'ven_fel_uuid' => 'TEST-UUID', 'ven_fel_status' => 'certified']);
        $this->mock(TekraFelService::class, function ($mock) {
            $mock->shouldReceive('anularFactura')->once()->andReturn(['resultado' => '{"error":0}', 'pdf_base64' => '']);
        });
        $page = new class extends \App\Filament\Resources\VentaResource\Pages\EditVenta
        {
            public function refreshFormData(array $attributes): void {}
        };
        $page->record = $sale;
        $actions = collect((new \ReflectionMethod($page, 'getHeaderActions'))->invoke($page))->keyBy(fn ($action) => $action->getName());
        $actions['anulation']->call(['data' => ['motivo' => 'Prueba aislada']]);
        self::assertSame('cancelled', $sale->fresh()->ven_estado);
        self::assertSame('void', $sale->fresh()->ven_fel_status);
        self::assertSame(10, $product->fresh()->pro_stock);
        self::assertSame(1, $product->movimientos()->where('mop_tipo', 'devolucion')->count());
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $actions['anulation']->call(['data' => ['motivo' => 'Segundo intento']]);
    }
}
