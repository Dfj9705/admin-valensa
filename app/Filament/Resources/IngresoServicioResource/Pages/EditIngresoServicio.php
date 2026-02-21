<?php

namespace App\Filament\Resources\IngresoServicioResource\Pages;

use App\Filament\Resources\IngresoServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIngresoServicio extends EditRecord
{
    protected static string $resource = IngresoServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
