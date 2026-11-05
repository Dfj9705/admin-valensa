<?php

namespace App\Services\Sales;

use App\Models\Venta;
use App\Models\VentaPago;
use App\Support\Access;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManagePago
{
    public function save(Venta $venta, array $data, ?VentaPago $pago = null): VentaPago
    {
        Access::authorize($pago ? 'pagos.editar' : 'pagos.crear');
        Access::authorize('pagos.ver');
        Access::authorize('ventas.ver');

        return DB::transaction(function () use ($venta, $data, $pago) {
            $venta = Venta::whereKey($venta->getKey())->lockForUpdate()->firstOrFail();
            $this->validateState($venta);
            $payments = $venta->pagos()->lockForUpdate()->get();
            if ($pago) {
                $pago = $payments->firstWhere('vpa_id', $pago->getKey());
                abort_unless($pago, 404);
            }
            $amount = round((float) ($data['vpa_monto'] ?? 0), 2);
            $paid = $payments->where('vpa_id', '!=', $pago?->getKey())->sum('vpa_monto');
            if ($amount <= 0 || round($paid + $amount, 2) > round((float) $venta->ven_total, 2)) {
                throw ValidationException::withMessages(['vpa_monto' => 'El monto debe ser positivo y no exceder el saldo pendiente.']);
            }
            $data = Arr::only($data, ['vpa_monto', 'vpa_metodo', 'vpa_referencia', 'vpa_fecha']);
            $data['vpa_monto'] = $amount;
            $data['vpa_updated_by'] = auth()->id();
            if ($pago) {
                $pago->update($data);

                return $pago;
            }
            $data['vpa_created_by'] = auth()->id();

            return $venta->pagos()->create($data);
        });
    }

    public function delete(VentaPago $pago): void
    {
        Access::authorize('pagos.eliminar');
        Access::authorize('pagos.ver');
        Access::authorize('ventas.ver');
        DB::transaction(function () use ($pago) {
            $venta = Venta::whereKey($pago->ven_id)->lockForUpdate()->firstOrFail();
            $this->validateState($venta);
            $venta->pagos()->whereKey($pago->getKey())->lockForUpdate()->firstOrFail()->delete();
        });
    }

    private function validateState(Venta $venta): void
    {
        if (! in_array($venta->ven_estado, ['draft', 'confirmed', 'certified'], true)) {
            throw ValidationException::withMessages(['venta' => 'No se pueden modificar pagos de una venta anulada.']);
        }
    }
}
