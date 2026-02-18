<?php

namespace App\Services\Sales;

use App\Models\ProductoMovimiento;
use App\Models\Venta;
use App\Models\Producto;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConfirmVenta
{
    /**
     * Confirma una venta (draft -> confirmed),
     * recalcula totales y descuenta stock de productos.
     */
    public function handle(Venta $venta, int $userId): Venta
    {
        if ($venta->ven_estado !== 'draft') {
            throw ValidationException::withMessages([
                'venta' => 'La venta no está en borrador.',
            ]);
        }

        // Cargar ítems y producto
        $venta->load('productos');

        if ($venta->productos->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'La venta no tiene productos.',
            ]);
        }

        return DB::transaction(function () use ($venta, $userId) {

            $subtotalConIvaIncluido = 0;

            foreach ($venta->productos as $productoVenta) {

                if (!$productoVenta) {
                    Notification::make()
                        ->title('Error')
                        ->body('Hay un ítem sin producto asociado.')
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'items' => 'Hay un ítem sin producto asociado.',
                    ]);
                }

                // ===== Recalcular línea (anti-manipulación) =====
                $qty = (float) ($productoVenta->qty ?? 0);
                $unitPrice = (float) ($productoVenta->unit_price ?? 0);
                $discount = (float) ($productoVenta->discount ?? 0);

                if ($qty <= 0) {
                    Notification::make()
                        ->title('Error')
                        ->body('Cantidad inválida.')
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'items' => 'Cantidad inválida.',
                    ]);
                }





                $subtotalConIvaIncluido += $productoVenta->line_total;

                $stockDisponible = $productoVenta->producto->pro_stock;

                if ($stockDisponible < $qty) {
                    Notification::make()
                        ->title('Error')
                        ->body("Stock insuficiente en {$productoVenta->producto->pro_nombre}. Disponible: {$stockDisponible}. Solicitado: {$qty}.")
                        ->danger()
                        ->send();
                    throw ValidationException::withMessages([
                        'items' => "Stock insuficiente en {$productoVenta->producto->pro_nombre}. Disponible: {$stockDisponible}. Solicitado: {$qty}.",
                    ]);
                }

                ProductoMovimiento::create([
                    'pro_id' => $productoVenta->producto->pro_id,
                    'mop_tipo' => 'venta',
                    'mop_cantidad' => $qty,
                    'mop_referencia_tipo' => 'VENTA',
                    'mop_referencia_id' => $venta->ven_id,
                    'mop_fecha' => now(),
                    'mop_observacion' => 'Venta confirmada',
                ]);
            }
            $subtotal = round($subtotalConIvaIncluido / 1.12, 2);
            $tax = round($subtotal * 0.12, 2);
            $total = round($subtotal + $tax, 2);

            $venta->update([
                'ven_subtotal' => $subtotal,
                'ven_tax' => $tax,
                'ven_total' => $total,
                'ven_estado' => 'confirmed',
                'ven_confirmed_at' => now(),
            ]);

            return $venta->fresh();
        });
    }

    /**
     * Anula una venta confirmada/certificada y devuelve stock.
     * Ajusta el estado permitido según tu flujo.
     */
    public function handleAnulation(Venta $venta, int $userId): Venta
    {
        if (!in_array($venta->ven_estado, ['confirmed', 'certified'], true)) {
            throw ValidationException::withMessages([
                'venta' => 'La venta no está confirmada/certificada para anulación.',
            ]);
        }

        $venta->load('productos');

        if ($venta->productos->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'La venta no tiene productos.',
            ]);
        }

        return DB::transaction(function () use ($venta, $userId) {

            foreach ($venta->productos as $producto) {

                if (!$producto) {
                    continue;
                }

                $qty = (float) ($producto->qty ?? 0);
                if ($qty <= 0) {
                    continue;
                }

                ProductoMovimiento::create([
                    'pro_id' => $producto->pro_id,
                    'mop_tipo' => 'devolucion',
                    'mop_cantidad' => $qty,
                    'mop_referencia_tipo' => 'VENTA',
                    'mop_referencia_id' => $venta->ven_id,
                    'mop_fecha' => now(),
                    'mop_observacion' => 'Venta anulada',
                ]);
            }

            $venta->update([
                'ven_estado' => 'cancelled', // o 'void' si manejas otro estado interno
                'updated_by' => $userId,
            ]);

            return $venta->fresh(['productos']);
        });
    }

}
