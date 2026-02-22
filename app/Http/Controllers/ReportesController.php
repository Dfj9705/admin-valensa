<?php

namespace App\Http\Controllers;

use App\Models\EgresoServicio;
use App\Models\Gasto;
use App\Models\IngresoServicio;
use App\Models\Producto;
use App\Models\Venta;
use App\Models\VentaPago;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class ReportesController extends Controller
{
    public function productos(Request $request)
    {
        $tableQuery = app(\App\Filament\Resources\ProductoResource::class)
            ->getEloquentQuery();

        $products = $tableQuery->get();

        $html = view('pdf.productos', compact('products'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->SetTitle('Listado de Productos');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('productos.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function servicios(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $ingresos = IngresoServicio::query()
            ->when($desde, fn($q) => $q->whereDate('ing_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('ing_fecha', '<=', $hasta))
            ->get()
            ->map(fn($i) => [
                'fecha' => optional($i->ing_fecha)->format('d/m/Y'),
                'tipo' => 'Ingreso',
                'origen' => 'Servicio',
                'detalle' => $i->ing_lugar,
                'monto' => (float) $i->ing_monto,
            ]);

        $egresos = EgresoServicio::query()
            ->when($desde, fn($q) => $q->whereDate('egr_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('egr_fecha', '<=', $hasta))
            ->get()
            ->map(fn($e) => [
                'fecha' => optional($e->egr_fecha)->format('d/m/Y'),
                'tipo' => 'Egreso',
                'origen' => 'Servicio',
                'detalle' => $e->categoria->cat_nombre,
                'monto' => (float) $e->egr_monto,
            ]);

        $movimientos = $ingresos->concat($egresos)
            ->sortBy(fn($m) => \Carbon\Carbon::createFromFormat('d/m/Y', $m['fecha'])->format('Y-m-d'))
            ->values();

        $html = view('pdf.servicios', compact('movimientos', 'desde', 'hasta'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->SetTitle('Reporte de Servicios');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('servicios.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function caja(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $pagos = VentaPago::query()
            ->when($desde, fn($q) => $q->whereDate('vpa_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('vpa_fecha', '<=', $hasta))
            ->get()
            ->map(fn($p) => [
                'fecha' => optional($p->vpa_fecha)->format('d/m/Y'),
                'tipo' => 'Ingreso',
                'origen' => 'Pago venta',
                'detalle' => $p->vpa_referencia,
                'monto' => (float) $p->vpa_monto,
            ]);

        $gastos = Gasto::query()
            ->when($desde, fn($q) => $q->whereDate('gas_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('gas_fecha', '<=', $hasta))
            ->get()
            ->map(fn($g) => [
                'fecha' => optional($g->gas_fecha)->format('d/m/Y'),
                'tipo' => 'Egreso',
                'origen' => 'Gasto',
                'detalle' => $g->categoria->cat_nombre,
                'monto' => (float) $g->gas_monto,
            ]);

        $movimientos = $pagos->concat($gastos)
            ->sortByDesc(fn($m) => \Carbon\Carbon::createFromFormat('d/m/Y', $m['fecha'])->format('Y-m-d'))
            ->values();

        $html = view('pdf.caja', compact('movimientos', 'desde', 'hasta'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->SetTitle('Reporte de Caja');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('caja.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }

    public function ventasPendientes(Request $request)
    {
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $ventas = Venta::query()
            ->select(['ven_id', 'created_at', 'ven_total', 'ven_cliente_id', 'ven_estado', 'ven_cliente_id'])
            ->withSum('pagos as pagos_sum', 'vpa_monto')
            ->when($desde, fn($q) => $q->whereDate('created_at', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('created_at', '<=', $hasta))
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($v) {
                $pagado = (float) ($v->pagos_sum ?? 0);
                $total = (float) $v->ven_total;
                $pendiente = $total - $pagado;

                return [
                    'id' => $v->ven_id,
                    'fecha' => optional($v->created_at)->format('d/m/Y'),
                    'total' => $total,
                    'pagado' => $pagado,
                    'pendiente' => $pendiente,
                    'estado' => $v->ven_estado,
                    'cliente' => $v->cliente->cli_nombre,
                ];
            });

        $totTotal = $ventas->sum('total');
        $totPagado = $ventas->sum('pagado');
        $totPendiente = $ventas->sum('pendiente');

        $html = view('pdf.ventas_pendientes', compact(
            'ventas',
            'desde',
            'hasta',
            'totTotal',
            'totPagado',
            'totPendiente'
        ))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 15,
            'margin_bottom' => 15,
        ]);

        $mpdf->SetTitle('Reporte de Ventas - Saldos Pendientes');
        $mpdf->WriteHTML($html);

        return response($mpdf->Output('ventas_pendientes.pdf', 'I'), 200)
            ->header('Content-Type', 'application/pdf');
    }
}
