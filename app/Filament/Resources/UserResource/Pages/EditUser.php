<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function beforeSave(): void
    {
        // Protección adicional del lado del servidor.
        if ($this->record->hasRole('superadministrador')) {
            throw ValidationException::withMessages([
                'data.roles' => 'No puedes modificar una cuenta superadministradora.',
            ]);
        }

        UserResource::validarRolOperativo(
            $this->form->getRawState()
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
