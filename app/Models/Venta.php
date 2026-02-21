<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';
    protected $primaryKey = 'ven_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ven_cliente_id',
        'ven_estado',
        'ven_subtotal',
        'ven_tax',
        'ven_total',
        'ven_confirmed_at',

        // FEL
        'ven_fel_uuid',
        'ven_fel_serie',
        'ven_fel_numero',
        'ven_fel_status',
        'ven_fel_fecha_hora_emision',
        'ven_fel_fecha_hora_certificacion',
        'ven_fel_nombre_receptor',
        'ven_fel_estado_documento',
        'ven_fel_nit_certificador',
        'ven_fel_nombre_certificador',
        'ven_fel_qr',
        'ven_fel_fecha_hora_anulacion',
        'ven_fel_motivo_anulacion',

        // Auditoría

        'ven_created_by',
        'ven_updated_by',

        'ven_emisor_id',
    ];

    protected $casts = [
        'ven_subtotal' => 'decimal:2',
        'ven_tax' => 'decimal:2',
        'ven_total' => 'decimal:2',

        'ven_confirmed_at' => 'datetime',
        'ven_fel_fecha_hora_emision' => 'datetime',
        'ven_fel_fecha_hora_certificacion' => 'datetime',
        // fel_fecha_hora_anulacion la dejaste string en DB
    ];

    // Relaciones
    public function productos(): HasMany
    {
        return $this->hasMany(VentaProducto::class, 'ven_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'ven_cliente_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function isEditable(): bool
    {
        return $this->ven_estado === 'draft';
    }

    public function emisor(): BelongsTo
    {
        return $this->belongsTo(Emisor::class, 'ven_emisor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'ven_id', 'ven_id');
    }

    public function getVenPagadoAttribute(): string
    {
        return (string) $this->pagos()->sum('vpa_monto');
    }

    public function getVenSaldoPendienteAttribute(): string
    {
        return (string) ((float) $this->ven_total - (float) $this->pagos()->sum('vpa_monto'));
    }
}
