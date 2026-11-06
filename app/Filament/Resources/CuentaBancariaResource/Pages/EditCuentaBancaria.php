<?php

namespace App\Filament\Resources\CuentaBancariaResource\Pages;

use App\Filament\Resources\CuentaBancariaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCuentaBancaria extends EditRecord
{
    protected static string $resource = CuentaBancariaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $cuenta = $this->record->fresh();

        if ($cuenta->tieneOperaciones()) {
            $camposProtegidos = [
                'cue_moneda',
                'cue_saldo_inicial',
                'cue_fecha_inicio',
            ];

            foreach ($camposProtegidos as $campo) {
                if (!array_key_exists($campo, $data)) {
                    continue;
                }

                $actual = $cuenta->{$campo};
                $nuevo = $data[$campo];

                if ($campo === 'cue_fecha_inicio') {
                    $actual = $actual?->format('Y-m-d');
                    $nuevo = $nuevo
                        ? \Carbon\Carbon::parse($nuevo)->format('Y-m-d')
                        : null;
                } elseif ($campo === 'cue_saldo_inicial') {
                    $actual = number_format((float) $actual, 2, '.', '');
                    $nuevo = number_format((float) $nuevo, 2, '.', '');
                }

                if ((string) $actual !== (string) $nuevo) {
                    throw ValidationException::withMessages([
                        $campo => 'Este campo no puede modificarse porque la cuenta ya tiene operaciones.',
                    ]);
                }
            }
        }

        return $data;
    }
}
