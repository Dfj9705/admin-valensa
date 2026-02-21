<?php

namespace App\Filament\Resources\EgresoServicioResource\Pages;

use App\Filament\Resources\EgresoServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEgresoServicios extends ListRecords
{
    protected static string $resource = EgresoServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
