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
        \App\Support\Access::authorize('ventas.stats');

        $inicioActual = now()->startOfQuarter();
        $finActual = now()->endOfQuarter();
        $trimestre = now()->quarter;

        $inicioAnterior = now()->subQuarter()->startOfQuarter();
        $finAnterior = now()->subQuarter()->endOfQuarter();

        // Ventas del trimestre actual
        $totalVentas = Venta::whereIn('ven_estado', ['confirmed', 'certified'])
            ->whereBetween('ven_confirmed_at', [
                $inicioActual,
                $finActual,
            ])
            ->sum('ven_total');

        // Ventas del trimestre anterior
        $totalVentasAnterior = Venta::whereIn('ven_estado', ['confirmed', 'certified'])
            ->whereBetween('ven_confirmed_at', [
                $inicioAnterior,
                $finAnterior,
            ])
            ->sum('ven_total');

        // Porcentaje de variación
        $porcentaje = $totalVentasAnterior > 0
            ? (($totalVentas - $totalVentasAnterior) / $totalVentasAnterior) * 100
            : null;

        $porcentaje = $porcentaje !== null
            ? round($porcentaje, 2)
            : null;
        $pagado = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->sum('vpa_monto');
        $totalVentasParaPendiente = Venta::whereIn('ven_estado', ['confirmed', 'certified'])->sum('ven_total');

        $pendiente = $totalVentasParaPendiente - $pagado;
        $canSeeExpenses = \App\Support\Access::allows('gastos.ver');
        $gastos = $canSeeExpenses ? Gasto::sum('gas_monto') : 0;
        $ingresosHoy = Venta::join('venta_pagos', 'ventas.ven_id', '=', 'venta_pagos.ven_id')->whereIn('ven_estado', ['confirmed', 'certified'])->whereDate('vpa_fecha', today())->sum('vpa_monto');
        $egresosHoy = $canSeeExpenses ? Gasto::whereDate('gas_fecha', today())->sum('gas_monto') : 0;

        return array_filter([

            Stat::make(
                "Ventas del trimestre {$trimestre}",
                'Q ' . number_format($totalVentas, 2)
            )
                ->description('Ventas confirmadas y certificadas')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
            // Stat::make('Pagado', 'Q ' . number_format($pagado, 2))->icon('heroicon-o-currency-dollar')->color('success'),
            Stat::make('Pendiente', 'Q ' . number_format($pendiente, 2))->icon('heroicon-o-currency-dollar')->color('danger'),
            // $canSeeExpenses ? Stat::make('Gastos', 'Q ' . number_format($gastos, 2))->icon('heroicon-o-currency-dollar')->color('danger') : null,
            $canSeeExpenses ? Stat::make('Balance Total', 'Q ' . number_format($pagado - $gastos, 2))->icon('heroicon-o-currency-dollar')->color('success') : null,
        ]);
    }

    public static function canView(): bool
    {
        return \App\Support\Access::allows('ventas.stats');
    }

}
