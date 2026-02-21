<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    use HasFactory;

    protected $table = 'venta_pagos';
    protected $primaryKey = 'vpa_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'ven_id',
        'vpa_monto',
        'vpa_metodo',
        'vpa_referencia',
        'vpa_fecha',
        'vpa_created_by',
        'vpa_updated_by',
    ];

    protected $casts = [
        'vpa_monto' => 'decimal:2',
        'vpa_fecha' => 'datetime',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'ven_id', 'ven_id');
    }
}
