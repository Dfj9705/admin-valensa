<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaBancaria extends Model
{
    protected $table = 'cuentas_bancarias';
    protected $primaryKey = 'cue_id';

    protected $fillable = [
        'ban_id',
        'cue_nombre',
        'cue_numero',
        'cue_tipo',
        'cue_moneda',
        'cue_activa',
        'cue_saldo_inicial',
        'cue_fecha_inicio',
    ];

    protected $casts = [
        'cue_activa' => 'boolean',
        'cue_saldo_inicial' => 'decimal:2',
        'cue_fecha_inicio' => 'date',
    ];

    public function banco(): BelongsTo
    {
        return $this->belongsTo(
            Banco::class,
            'ban_id',
            'ban_id'
        );
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(
            VentaPago::class,
            'cue_id',
            'cue_id'
        );
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(
            Gasto::class,
            'cue_id',
            'cue_id'
        );
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(
            MovimientoBancario::class,
            'cue_id',
            'cue_id'
        );
    }

    public function calcularSaldoActual(): float
    {
        $desde = $this->cue_fecha_inicio?->toDateString();

        $ingresosVentas = $this->pagos()
            ->when(
                $desde,
                fn($query) =>
                    $query->whereDate('vpa_fecha', '>=', $desde)
            )
            ->sum('vpa_monto');

        $egresosGastos = $this->gastos()
            ->when(
                $desde,
                fn($query) =>
                    $query->whereDate('gas_fecha', '>=', $desde)
            )
            ->sum('gas_monto');

        $otrosIngresos = $this->movimientos()
            ->where('mov_tipo', 'ingreso')
            ->when(
                $desde,
                fn($query) =>
                    $query->whereDate('mov_fecha', '>=', $desde)
            )
            ->sum('mov_monto');

        $otrosEgresos = $this->movimientos()
            ->where('mov_tipo', 'egreso')
            ->when(
                $desde,
                fn($query) =>
                    $query->whereDate('mov_fecha', '>=', $desde)
            )
            ->sum('mov_monto');

        return round(
            (float) $this->cue_saldo_inicial
            + (float) $ingresosVentas
            + (float) $otrosIngresos
            - (float) $egresosGastos
            - (float) $otrosEgresos,
            2
        );
    }

    public function tieneOperaciones(): bool
    {
        return $this->pagos()->exists()
            || $this->gastos()->exists()
            || $this->movimientos()->exists();
    }

    protected static function booted(): void
    {
        static::creating(function (CuentaBancaria $cuenta) {
            if (auth()->check()) {
                $cuenta->cue_created_by = auth()->id();
            }
        });

        static::updating(function (CuentaBancaria $cuenta) {
            if (auth()->check()) {
                $cuenta->cue_updated_by = auth()->id();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cue_created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cue_updated_by', 'id');
    }
}
