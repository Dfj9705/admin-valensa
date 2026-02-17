<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Services\Sales\ConfirmSale;
use App\Services\Sales\ConfirmVenta;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Storage;
use Throwable;

class EditVenta extends EditRecord
{
    protected static string $resource = VentaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->icon('heroicon-o-trash')->color('danger'),
            Action::make('confirm')
                ->label('Confirmar venta')
                ->icon('heroicon-o-check-circle')
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
            Action::make('print')
                ->label('Imprimir')
                ->color('primary')
                ->icon('heroicon-o-printer')
                ->visible(fn() => $this->record->ven_estado === 'confirmed')
                ->action(function () {
                    $venta = $this->record;
                    $mpdf = new Mpdf([
                        'mode' => 'utf-8',
                        'format' => 'Letter',
                        'orientation' => 'P',
                        'default_font_size' => 10,
                        'default_font' => 'dejavusans',
                    ]);
                    $html = view('pdf.recibo', compact('venta'))->render();
                    $mpdf->WriteHTML($html);
                    $nombre = "recibo_{$venta->ven_id}_{$venta->created_at->format('Ymd')}.pdf";
                    $pdfBinary = $mpdf->Output($nombre, Destination::STRING_RETURN);
                    $relativePath = "recibos/{$nombre}";
                    Storage::disk('public')->put($relativePath, $pdfBinary);

                    Notification::make()
                        ->title('Recibo generado')
                        ->success()
                        ->send();

                    $this->redirect(Storage::disk('public')->url($relativePath));
                }),
        ];
    }
}
