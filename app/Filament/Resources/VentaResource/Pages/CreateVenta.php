<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVenta extends CreateRecord
{
    protected static string $resource = VentaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (array_keys($data) as $key) {
            if (str_starts_with($key, 'ven_fel_')) { unset($data[$key]); }
        }
        $data['ven_estado'] = 'draft';
        $data['ven_created_by'] = auth()->id();
        return $data;
    }

}
