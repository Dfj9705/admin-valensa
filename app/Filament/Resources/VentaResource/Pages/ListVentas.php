<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListVentas extends ListRecords
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Action::make('imprimir_saldos')
                ->label('Imprimir saldos pendientes')
                ->icon('heroicon-o-printer')
                ->url(fn() => route('reportes.ventas.pendientes'))
                ->openUrlInNewTab(),
        ];
    }
}
