<?php

namespace App\Filament\Resources\CategoriaGastoResource\Pages;

use App\Filament\Resources\CategoriaGastoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaGastos extends ListRecords
{
    protected static string $resource = CategoriaGastoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
