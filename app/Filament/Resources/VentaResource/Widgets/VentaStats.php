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

        $totalVentas = Venta::whereIn('ven_estado', ['confirmed', 'certified'])->sum('ven_total');
        $pagado = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->sum('vpa_monto');
        $pendiente = $totalVentas - $pagado;
        $gastos = Gasto::sum('gas_monto');
        $ingresosHoy = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->whereDate('vpa_fecha', today())->sum('vpa_monto');
        $egresosHoy = Gasto::whereDate('gas_fecha', today())->sum('gas_monto');

        return [
            Stat::make('Total Ventas', 'Q ' . number_format($totalVentas, 2))->icon('heroicon-o-currency-dollar')->color('success'),
            Stat::make('Pagado', 'Q ' . number_format($pagado, 2))->icon('heroicon-o-currency-dollar')->color('success'),
            Stat::make('Pendiente', 'Q ' . number_format($pendiente, 2))->icon('heroicon-o-currency-dollar')->color('danger'),
            Stat::make('Gastos', 'Q ' . number_format($gastos, 2))->icon('heroicon-o-currency-dollar')->color('danger'),
            Stat::make('Balance Total', 'Q ' . number_format($pagado - $gastos, 2))->icon('heroicon-o-currency-dollar')->color('success'),
        ];
    }
}
