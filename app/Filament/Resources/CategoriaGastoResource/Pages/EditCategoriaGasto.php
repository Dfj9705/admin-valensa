<?php

namespace App\Filament\Resources\CategoriaGastoResource\Pages;

use App\Filament\Resources\CategoriaGastoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategoriaGasto extends EditRecord
{
    protected static string $resource = CategoriaGastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
