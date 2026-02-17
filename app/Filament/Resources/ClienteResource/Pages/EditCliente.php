<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $nit = ClienteResource::cleanId($data['cli_nit'] ?? null);
        $cui = ClienteResource::cleanId($data['cli_cui'] ?? null);

        if (blank($nit) && blank($cui)) {
            Notification::make()
                ->title('Ingresa NIT o CUI')
                ->danger()
                ->send();
            throw ValidationException::withMessages([
                'data.cli_nit' => 'Ingresa NIT o CUI.',
                'data.cli_cui' => 'Ingresa NIT o CUI.',
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
