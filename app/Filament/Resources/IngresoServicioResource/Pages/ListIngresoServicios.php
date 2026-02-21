<?php

namespace App\Filament\Resources\IngresoServicioResource\Pages;

use App\Filament\Resources\IngresoServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIngresoServicios extends ListRecords
{
    protected static string $resource = IngresoServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
