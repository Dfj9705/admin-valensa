<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Services\Sales\ConfirmSale;
use App\Services\Sales\ConfirmVenta;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Throwable;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('confirm')
                ->label('Confirmar venta')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->ven_estado === 'draft')
                ->action(function () {
                    try {
                        app(ConfirmVenta::class)->handle($this->record, auth()->id());

                        Notification::make()
                            ->title('Venta confirmada')
                            ->success()
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'subtotal',
                            'tax',
                            'total',
                            'confirmed_at',
                        ]);
                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('No se pudo confirmar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
