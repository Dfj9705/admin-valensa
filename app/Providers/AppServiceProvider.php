<?php

namespace App\Providers;

use App\Support\Access;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Recheck reads on subsequent Livewire requests, including revoked permissions.
        $authorizeComponent = function ($component): void {
            if ($component instanceof \Filament\Widgets\Widget) {
                abort_unless($component::canView(), 403);
            }
            if ($component instanceof \Filament\Resources\Pages\Page) {
                abort_unless($component::getResource()::canViewAny(), 403);
            }
            if ($component instanceof \App\Filament\Pages\ReportadorFinanciero) {
                Access::authorize('reportes.ver');
            }
            if ($component instanceof \App\Filament\Resources\ProductoResource\RelationManagers\MovimientosRelationManager) {
                Access::authorize('inventario.ver');
                Access::authorize('productos.ver');
            }
            if ($component instanceof \App\Filament\Resources\VentaResource\RelationManagers\PagosRelationManager) {
                Access::authorize('pagos.ver');
                Access::authorize('ventas.ver');
            }
            $class = get_class($component);
            if (str_starts_with($class, 'TomatoPHP\\FilamentSettingsHub\\Pages\\')
                || str_starts_with($class, 'TomatoPHP\\FilamentPWA\\Filament\\Pages\\')) {
                abort_unless(auth()->user()?->hasRole('superadministrador'), 403);
            }
        };
        \Livewire\before('mount', $authorizeComponent);
        \Livewire\before('hydrate', $authorizeComponent);
    }
}
