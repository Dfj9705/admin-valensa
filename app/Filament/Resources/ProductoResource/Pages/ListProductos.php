<?php

namespace App\Filament\Resources\ProductoResource\Pages;

use App\Filament\Resources\ProductoResource;
use App\Models\Producto;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Mpdf\Mpdf;

class ListProductos extends ListRecords
{
    protected static string $resource = ProductoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('Imprimir lista')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->label('Imprimir lista')
                ->url(fn() => route('products.print'), true),
        ];
    }
}
