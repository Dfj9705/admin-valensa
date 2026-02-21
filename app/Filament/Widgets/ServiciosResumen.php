<?php

namespace App\Filament\Widgets;

use App\Models\EgresoServicio;
use App\Models\IngresoServicio;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ServiciosResumen extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static ?string $title = 'Resumen de servicios';
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();
        $finMes = Carbon::now()->endOfMonth();

        $ingresosMes = (float) IngresoServicio::query()
            ->whereBetween('ing_fecha', [$inicioMes, $finMes])
            ->sum('ing_monto');

        $egresosMes = (float) EgresoServicio::query()
            ->whereBetween('egr_fecha', [$inicioMes, $finMes])
            ->sum('egr_monto');

        $saldoMes = $ingresosMes - $egresosMes;

        $ingresosHoy = (float) IngresoServicio::query()
            ->whereDate('ing_fecha', $hoy)
            ->sum('ing_monto');

        $egresosHoy = (float) EgresoServicio::query()
            ->whereDate('egr_fecha', $hoy)
            ->sum('egr_monto');

        return [
            Stat::make('Ingresos (mes)', $this->gtq($ingresosMes))
                ->description('Servicios prestados')
                ->icon('heroicon-o-arrow-trending-up'),

            Stat::make('Egresos (mes)', $this->gtq($egresosMes))
                ->description('Gastos por servicios')
                ->icon('heroicon-o-arrow-trending-down'),

            Stat::make('Saldo (mes)', $this->gtq($saldoMes))
                ->description('Ingresos - Egresos')
                ->icon('heroicon-o-scale'),

            Stat::make('Hoy', $this->gtq($ingresosHoy) . ' / ' . $this->gtq($egresosHoy))
                ->description('Ingresos / Egresos')
                ->icon('heroicon-o-calendar-days'),
        ];
    }

    private function gtq(float $monto): string
    {
        return 'GTQ ' . number_format($monto, 2, '.', ',');
    }
}
