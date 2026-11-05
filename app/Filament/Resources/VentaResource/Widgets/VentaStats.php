<?php

namespace App\Filament\Resources\VentaResource\Widgets;

use App\Models\Gasto;
use App\Models\Venta;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VentaStats extends BaseWidget
{
    protected int|string|array $columnSpan = '3';
    protected function getStats(): array
    {
        \App\Support\Access::authorize('ventas.ver');

        $totalVentas = Venta::whereIn('ven_estado', ['confirmed', 'certified'])->sum('ven_total');
        $pagado = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->sum('vpa_monto');
        $pendiente = $totalVentas - $pagado;
        $canSeeExpenses = \App\Support\Access::allows('gastos.ver');
        $gastos = $canSeeExpenses ? Gasto::sum('gas_monto') : 0;
        $ingresosHoy = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->whereDate('vpa_fecha', today())->sum('vpa_monto');
        $egresosHoy = $canSeeExpenses ? Gasto::whereDate('gas_fecha', today())->sum('gas_monto') : 0;

        return array_filter([
            Stat::make('Total Ventas', 'Q ' . number_format($totalVentas, 2))->icon('heroicon-o-currency-dollar')->color('success'),
            Stat::make('Pagado', 'Q ' . number_format($pagado, 2))->icon('heroicon-o-currency-dollar')->color('success'),
            Stat::make('Pendiente', 'Q ' . number_format($pendiente, 2))->icon('heroicon-o-currency-dollar')->color('danger'),
            $canSeeExpenses ? Stat::make('Gastos', 'Q ' . number_format($gastos, 2))->icon('heroicon-o-currency-dollar')->color('danger') : null,
            $canSeeExpenses ? Stat::make('Balance Total', 'Q ' . number_format($pagado - $gastos, 2))->icon('heroicon-o-currency-dollar')->color('success') : null,
        ]);
    }

    public static function canView(): bool { return \App\Support\Access::allows('ventas.ver'); }

}
