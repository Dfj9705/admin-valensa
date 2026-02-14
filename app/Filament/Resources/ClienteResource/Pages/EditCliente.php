<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $nit = ClienteResource::cleanId($data['nit'] ?? null);
        $cui = ClienteResource::cleanId($data['cui'] ?? null);

        if (blank($nit) && blank($cui)) {
            throw ValidationException::withMessages([
                'data.nit' => 'Ingresa NIT o CUI.',
                'data.cui' => 'Ingresa NIT o CUI.',
            ]);
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
