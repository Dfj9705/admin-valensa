<?php

namespace App\Filament\Widgets;

use App\Models\ProductoMovimiento;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MovementsChart extends ChartWidget
{
    protected static ?string $heading = 'Movimientos por Producto (Top 10)';
    protected int|string|array $columnSpan = 'full';
    protected function getData(): array
    {
        $from = now()->subDays(30);

        $data = ProductoMovimiento::query()
            ->selectRaw('pro_id, mop_tipo, SUM(mop_cantidad) as total')
            ->where('created_at', '>=', $from)
            ->groupBy('pro_id', 'mop_tipo')
            ->with('producto:pro_id,pro_nombre')
            ->get();

        // Agrupar por producto
        $grouped = $data->groupBy('pro_id');

        // Ordenar por total general y tomar top 10
        $topProducts = $grouped->map(function ($items) {
            return $items->sum('total');
        })->sortDesc()->take(10);

        $labels = [];
        $entrada = [];
        $salida = [];
        $venta = [];
        $devolucion = [];

        foreach ($topProducts as $productId => $sum) {
            $items = $grouped[$productId];

            $labels[] = $items->first()->producto->pro_nombre ?? 'N/A';

            $entrada[] = (float) ($items->firstWhere('mop_tipo', 'entrada')->total ?? 0);
            $salida[] = (float) ($items->firstWhere('mop_tipo', 'salida')->total ?? 0);
            $venta[] = (float) ($items->firstWhere('mop_tipo', 'venta')->total ?? 0);
            $devolucion[] = (float) ($items->firstWhere('mop_tipo', 'devolucion')->total ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entrada',
                    'data' => $entrada,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Salida',
                    'data' => $salida,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Venta',
                    'data' => $venta,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Devolución',
                    'data' => $devolucion,
                    'backgroundColor' => 'rgba(255, 165, 0, 0.2)',
                    'borderColor' => 'rgba(255, 165, 0, 1)',
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
        ];
    }
}
