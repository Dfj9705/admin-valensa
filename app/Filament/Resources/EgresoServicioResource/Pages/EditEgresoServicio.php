<?php

namespace App\Filament\Resources\EgresoServicioResource\Pages;

use App\Filament\Resources\EgresoServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEgresoServicio extends EditRecord
{
    protected static string $resource = EgresoServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
