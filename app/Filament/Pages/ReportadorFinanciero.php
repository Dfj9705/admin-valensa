<?php

namespace App\Filament\Pages;

use App\Models\EgresoServicio;
use App\Models\Gasto;
use App\Models\IngresoServicio;
use App\Models\Venta;
use App\Models\VentaPago;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Mpdf\Mpdf;

class ReportadorFinanciero extends Page
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reportador';
    protected static ?string $title = 'Reportador financiero';
    protected static string $view = 'filament.pages.reportador-financiero';
    protected static ?int $navigationSort = 5;

    public ?string $desde = null;
    public ?string $hasta = null;

    public function mount(): void
    {
        // por defecto: mes actual
        $this->desde = now()->startOfMonth()->toDateString();
        $this->hasta = now()->toDateString();

        $this->form->fill([
            'desde' => $this->desde,
            'hasta' => $this->hasta,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros')
                    ->columns(2)
                    ->schema([
                        DatePicker::make('desde')
                            ->label('Desde')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn($state) => $this->desde = $state),

                        DatePicker::make('hasta')
                            ->label('Hasta')
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn($state) => $this->hasta = $state),
                    ]),
            ])
            ->statePath('');
    }

    private function money(float $n): string
    {
        return 'Q ' . number_format($n, 2, '.', ',');
    }

    public function getIngresosServicios(): float
    {
        return (float) IngresoServicio::query()
            ->when($this->desde, fn($q) => $q->whereDate('ing_fecha', '>=', $this->desde))
            ->when($this->hasta, fn($q) => $q->whereDate('ing_fecha', '<=', $this->hasta))
            ->sum('ing_monto');
    }

    public function getEgresosServicios(): float
    {
        return (float) EgresoServicio::query()
            ->when($this->desde, fn($q) => $q->whereDate('egr_fecha', '>=', $this->desde))
            ->when($this->hasta, fn($q) => $q->whereDate('egr_fecha', '<=', $this->hasta))
            ->sum('egr_monto');
    }

    public function getGastosGenerales(): float
    {
        return (float) Gasto::query()
            ->when($this->desde, fn($q) => $q->whereDate('gas_fecha', '>=', $this->desde))
            ->when($this->hasta, fn($q) => $q->whereDate('gas_fecha', '<=', $this->hasta))
            ->sum('gas_monto');
    }

    public function getVentasTotal(): float
    {
        return (float) Venta::query()
            ->when($this->desde, fn($q) => $q->whereDate('created_at', '>=', $this->desde))
            ->when($this->hasta, fn($q) => $q->whereDate('created_at', '<=', $this->hasta))
            ->sum('ven_total');
    }

    public function getPagosVentas(): float
    {
        // Pagos asociados a ventas creadas en el rango (tu criterio actual)
        return (float) VentaPago::query()
            ->whereHas('venta', function ($q) {
                $q->when($this->desde, fn($qq) => $qq->whereDate('created_at', '>=', $this->desde))
                    ->when($this->hasta, fn($qq) => $qq->whereDate('created_at', '<=', $this->hasta));
            })
            ->sum('vpa_monto');
    }

    public function getSaldoServicios(): float
    {
        return $this->getIngresosServicios() - $this->getEgresosServicios();
    }

    public function getSaldoPendienteVentas(): float
    {
        return $this->getVentasTotal() - $this->getPagosVentas();
    }

    public function getCajaGlobal(): float
    {
        // Caja: (ingresos servicios + pagos ventas) - (egresos servicios + gastos)
        return ($this->getPagosVentas())
            - ($this->getGastosGenerales());
    }

    // Helpers formateados para la vista
    public function cards(): array
    {
        return [
            ['label' => 'Ingresos servicios', 'value' => $this->money($this->getIngresosServicios())],
            ['label' => 'Egresos servicios', 'value' => $this->money($this->getEgresosServicios())],
            ['label' => 'Saldo servicios', 'value' => $this->money($this->getSaldoServicios())],
            ['label' => 'Ventas total', 'value' => $this->money($this->getVentasTotal())],
            ['label' => 'Pagado (ventas)', 'value' => $this->money($this->getPagosVentas())],
            ['label' => 'Pendiente (ventas)', 'value' => $this->money($this->getSaldoPendienteVentas())],
            ['label' => 'Gastos generales', 'value' => $this->money($this->getGastosGenerales())],
            ['label' => 'Saldo Maquinaria', 'value' => $this->money($this->getCajaGlobal())],
        ];
    }

    public function ventasConSaldo()
    {
        // sin N+1: trae suma de pagos por venta
        return Venta::query()
            ->select(['ven_id', 'created_at', 'ven_total'])
            ->withSum('pagos as pagos_sum', 'vpa_monto')
            ->when($this->desde, fn($q) => $q->whereDate('created_at', '>=', $this->desde))
            ->when($this->hasta, fn($q) => $q->whereDate('created_at', '<=', $this->hasta))
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($v) {
                $pagado = (float) ($v->pagos_sum ?? 0);
                $pendiente = (float) $v->ven_total - $pagado;

                return [
                    'id' => $v->ven_id,
                    'fecha' => optional($v->created_at)->format('d/m/Y'),
                    'total' => $this->money((float) $v->ven_total),
                    'pagado' => $this->money($pagado),
                    'pendiente' => $this->money($pendiente),
                ];
            });
    }

    public function movimientosCaja(): Collection
    {
        $desde = $this->desde;
        $hasta = $this->hasta;

        $pagos = VentaPago::query()
            ->select(['vpa_id', 'ven_id', 'vpa_fecha', 'vpa_monto', 'vpa_metodo', 'vpa_referencia'])
            ->when($desde, fn($q) => $q->whereDate('vpa_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('vpa_fecha', '<=', $hasta))
            ->get()
            ->map(function ($p) {
                return [
                    'fecha' => optional($p->vpa_fecha)->format('Y-m-d'),
                    'fecha_fmt' => optional($p->vpa_fecha)->format('d/m/Y'),
                    'tipo' => 'Ingreso',
                    'origen' => 'Pago venta',
                    'monto' => (float) $p->vpa_monto,
                    'monto_fmt' => '+ Q ' . number_format((float) $p->vpa_monto, 2, '.', ','),
                    'detalle' => 'Venta #' . $p->ven_id
                        . ($p->vpa_metodo ? ' · ' . $p->vpa_metodo : '')
                        . ($p->vpa_referencia ? ' · Ref: ' . $p->vpa_referencia : ''),
                ];
            });

        $gastos = Gasto::query()
            ->select(['gas_id', 'gas_fecha', 'gas_monto', 'cat_id'])
            ->when($desde, fn($q) => $q->whereDate('gas_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('gas_fecha', '<=', $hasta))
            ->get()
            ->map(function ($g) {
                return [
                    'fecha' => optional($g->gas_fecha)->format('Y-m-d'),
                    'fecha_fmt' => optional($g->gas_fecha)->format('d/m/Y'),
                    'tipo' => 'Egreso',
                    'origen' => 'Gasto',
                    'monto' => (float) $g->gas_monto,
                    'monto_fmt' => '- Q ' . number_format((float) $g->gas_monto, 2, '.', ','),
                    'detalle' => $g->categoria ? ('Categoría ' . $g->categoria->cat_nombre) : 'Sin categoría',
                ];
            });

        return $pagos
            ->concat($gastos)
            ->sortByDesc('fecha')   // ✅ orden por fecha (desc)
            ->values();
    }

    public function movimientosServicios(): Collection
    {
        $desde = $this->desde;
        $hasta = $this->hasta;

        $ingresos = IngresoServicio::query()
            ->select(['ing_id', 'ing_fecha', 'ing_monto'])
            ->when($desde, fn($q) => $q->whereDate('ing_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('ing_fecha', '<=', $hasta))
            ->get()
            ->map(function ($i) {
                return [
                    'fecha' => optional($i->ing_fecha)->format('Y-m-d'),
                    'fecha_fmt' => optional($i->ing_fecha)->format('d/m/Y'),
                    'tipo' => 'Ingreso',
                    'detalle' => $i->ing_lugar,
                    'monto' => (float) $i->ing_monto,
                ];
            });

        $egresos = EgresoServicio::query()
            ->select(['egr_id', 'egr_fecha', 'egr_monto'])
            ->when($desde, fn($q) => $q->whereDate('egr_fecha', '>=', $desde))
            ->when($hasta, fn($q) => $q->whereDate('egr_fecha', '<=', $hasta))
            ->get()
            ->map(function ($e) {
                return [
                    'fecha' => optional($e->egr_fecha)->format('Y-m-d'),
                    'fecha_fmt' => optional($e->egr_fecha)->format('d/m/Y'),
                    'tipo' => 'Egreso',
                    'detalle' => $e->egr_lugar,
                    'monto' => -(float) $e->egr_monto,
                ];
            });

        $movimientos = $ingresos
            ->concat($egresos)
            ->sortBy('fecha')
            ->values();

        $saldo = 0;

        return $movimientos->map(function ($m) use (&$saldo) {
            $saldo += $m['monto'];

            return [
                'fecha_fmt' => $m['fecha_fmt'],
                'tipo' => $m['tipo'],
                'monto' => $m['monto'],
                'monto_fmt' => 'Q ' . number_format(abs($m['monto']), 2, '.', ','),
                'detalle' => $m['detalle'],
                'saldo' => $saldo,
                'saldo_fmt' => 'Q ' . number_format($saldo, 2, '.', ','),
            ];
        });
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print_servicios')
                ->label('Saldo Servicios')
                ->icon('heroicon-o-printer')
                ->url(fn() => route('reportes.servicios', [
                    'desde' => $this->desde,
                    'hasta' => $this->hasta,
                ]))
                ->openUrlInNewTab(),

            Action::make('print_caja')
                ->label('Saldo Maquinaria')
                ->icon('heroicon-o-printer')
                ->url(fn() => route('reportes.caja', [
                    'desde' => $this->desde,
                    'hasta' => $this->hasta,
                ]))
                ->openUrlInNewTab(),
        ];
    }


}
