<?php

namespace App\Filament\Resources\VentaResource\Pages;

use App\Filament\Resources\VentaResource;
use App\Services\Sales\ConfirmSale;
use App\Services\Sales\ConfirmVenta;
use App\Services\Tekra\TekraFelService;
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
            DeleteAction::make()->icon('heroicon-o-trash')->color('danger')->visible(fn() => $this->record->ven_estado === 'draft'),
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
                            'ven_estado',
                            'ven_subtotal',
                            'ven_tax',
                            'ven_total',
                            'ven_confirmed_at',
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
            Action::make('certify')
                ->label('Certificar FEL')
                ->icon('heroicon-o-check-circle')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn() => $this->record->ven_estado === 'confirmed' && empty($this->record->fel_uuid))
                ->action(function () {
                    try {
                        $venta = $this->record->fresh();
                        $certificador = new TekraFelService();
                        $respuesta = $certificador->certificarFactura($venta);

                        // 1) Si trae PDF base64 directo
                        $resultRaw = $respuesta['raw'];
                        $pdfBase64 = $respuesta['pdf_base64'] ?? '';
                        $resultado = json_decode($respuesta['resultado']) ?? '';
                        $documento_certificado = $respuesta['documento_certificado'] ?? '';
                        $pdf_base64 = $respuesta['pdf_base64'] ?? '';
                        $qrCode = $respuesta['qr'] ?? '';

                        logger($resultRaw->NumeroAutorizacion);
                        logger($resultado->error);
                        if ($resultado->error == 1) {
                            $messages = $resultado->frases;
                            foreach ($messages as $message) {
                                Notification::make()
                                    ->title($message)
                                    ->warning()
                                    ->send();
                            }
                            return;
                        }

                        if ($pdfBase64) {
                            $pdfPath = "fel/sale-{$venta->ven_id}.pdf";
                            Storage::disk('public')->put($pdfPath, base64_decode(trim($pdfBase64)));
                        }

                        // 2) Parsear DocumentoCertificado para UUID/serie/numero (cuando viene dentro del XML)
                        $uuid = null;
                        $serie = null;
                        $numero = null;
                        $resultado = null;
                        $fechaHoraCertificacion = null;
                        $fechaHoraEmision = null;
                        $nitCertificador = null;
                        $nombreCertificador = null;
                        $estadoDocumento = null;
                        $nombreReceptor = null;



                        logger(json_encode($resultRaw));
                        if ($resultRaw) {
                            $uuid = $resultRaw->NumeroAutorizacion;
                            $serie = $resultRaw->SerieDocumento;
                            $numero = $resultRaw->NumeroDocumento;
                            $fechaHoraCertificacion = $resultRaw->FechaHoraCertificacion;
                            $nitCertificador = $resultRaw->NITCertificador;
                            $nombreCertificador = $resultRaw->NombreCertificador;
                            $estadoDocumento = $resultRaw->EstadoDocumento;
                            $nombreReceptor = $resultRaw->NombreReceptor ?? $venta->cliente->cli_nombre;
                            $fechaHoraEmision = $resultRaw->FechaHoraEmision;
                        }


                        logger($uuid);

                        if (!$uuid) {
                            // si no logramos extraerlo, marca error para revisar
                            $venta->update(['ven_fel_status' => 'error']);
                            Notification::make()->title('FEL no retornó UUID')->danger()->send();
                            return;
                        }

                        $venta->update([
                            'ven_fel_uuid' => $uuid,
                            'ven_fel_serie' => $serie,
                            'ven_fel_numero' => $numero,
                            'ven_fel_fecha_hora_certificacion' => str_replace('-06:00', '', str_replace('T', ' ', $fechaHoraCertificacion)),
                            'ven_fel_nit_certificador' => $nitCertificador,
                            'ven_fel_nombre_certificador' => $nombreCertificador,
                            'ven_fel_estado_documento' => $estadoDocumento,
                            'ven_fel_nombre_receptor' => $nombreReceptor,
                            'ven_fel_fecha_hora_emision' => str_replace('-06:00', '', str_replace('T', ' ', $fechaHoraEmision)),
                            'ven_fel_status' => 'certified',
                            'ven_estado' => 'certified',
                            'ven_fel_qr' => $qrCode
                        ]);


                        Notification::make()
                            ->title('Documento certificado')
                            ->body("UUID: {$uuid}")
                            ->success()
                            ->send();
                        $this->record = $this->record->fresh();
                        $this->refreshFormData(['ven_estado', 'fel_uuid', 'fel_serie', 'fel_numero', 'fel_fecha_hora_certificacion', 'fel_nit_certificador', 'fel_nombre_certificador', 'fel_estado_documento', 'fel_nombre_receptor', 'fel_fecha_hora_emision', 'fel_status']);

                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('No se pudo certificar')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Action::make('download_pdf')
                ->label('Imprimir Factura')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->visible(fn() => $this->record->ven_fel_status === 'certified' && $this->record->ven_estado === 'certified')
                ->action(function () {
                    $venta = $this->record;
                    $html = view('pdf.factura', compact('venta'))->render();

                    $mpdf = new Mpdf([
                        'format' => 'Letter',
                        'margin_left' => 10,
                        'margin_right' => 10,
                        'margin_top' => 10,
                        'margin_bottom' => 10,
                        'default_font' => 'dejavusans',
                        'default_font_size' => 9,
                    ]);


                    $mpdf->WriteHTML($html);
                    $pdfBinary = $mpdf->Output("factura_{$venta->ven_fel_serie}_{$venta->ven_fel_numero}.pdf", Destination::STRING_RETURN);

                    // 4) Guardar en storage/public (para poder abrirlo)
                    $relativePath = "fel/factura_{$venta->ven_fel_serie}_{$venta->ven_fel_numero}.pdf";
                    Storage::disk('public')->put($relativePath, $pdfBinary);

                    // 5) Notificar y abrir URL
                    Notification::make()
                        ->title('Factura generada')
                        ->success()
                        ->send();

                    $this->redirect(Storage::disk('public')->url($relativePath));
                }),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar cambios')
                ->color('primary')
                ->visible(fn() => $this->record->ven_estado === 'draft')
                ->action(function () {
                    $this->save();
                }),
            Action::make('cancel')
                ->label('Cancelar')
                ->color('danger')
                ->action(function () {
                    $this->redirect(VentaResource::getUrl());
                }),
        ];
    }
}
