<?php

namespace App\Filament\Resources\CuentaBancariaResource\Pages;

use App\Filament\Resources\CuentaBancariaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCuentaBancarias extends ListRecords
{
    protected static string $resource = CuentaBancariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reporteGeneral')
                ->label('Reporte general')
                ->icon('heroicon-o-document-chart-bar')
                ->color('success')
                ->visible(
                    fn() =>
                        \App\Support\Access::allows('reportes.ver')
                        && \App\Support\Access::allows('reportes.exportar')
                )
                ->form([
                    \Filament\Forms\Components\DatePicker::make('desde')
                        ->label('Desde')
                        ->required()
                        ->default(now()->startOfMonth()),

                    \Filament\Forms\Components\DatePicker::make('hasta')
                        ->label('Hasta')
                        ->required()
                        ->default(now())
                        ->afterOrEqual('desde'),
                ])
                ->action(
                    fn(array $data) =>
                        redirect()->route('reportes.cuentas-bancarias', [
                            'desde' => $data['desde'],
                            'hasta' => $data['hasta'],
                        ])
                ),

            Actions\CreateAction::make(),
        ];
    }
}
