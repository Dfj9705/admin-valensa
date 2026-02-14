<?php

namespace App\Filament\Widgets;

use App\Models\Producto;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStats extends BaseWidget
{
    protected function getStats(): array
    {
        $products = Producto::all();

        $totalStock = $products->sum(fn($product) => $product->pro_stock);
        $outOfStock = $products->filter(fn($product) => $product->pro_stock <= 0)->count();
        $lowStock = $products->filter(fn($product) => $product->pro_stock > 0 && $product->pro_stock <= 5)->count();

        return [
            Stat::make('Productos Totales', $products->count())
                ->color('primary'),

            Stat::make('Stock Total', $totalStock)
                ->color('success'),

            Stat::make('Sin Stock', $outOfStock)
                ->color('danger'),

            Stat::make('Stock Bajo', $lowStock)
                ->color('warning'),
        ];
    }
}
