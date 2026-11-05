<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaVencimiento extends Model
{
    protected $table = 'venta_vencimientos';

    protected $primaryKey = 'vve_id';

    protected $fillable = [
        'ven_id',
        'vve_numero',
        'vve_fecha_vencimiento',
        'vve_monto',
    ];

    protected $casts = [
        'vve_numero' => 'integer',
        'vve_fecha_vencimiento' => 'date',
        'vve_monto' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(
            Venta::class,
            'ven_id',
            'ven_id'
        );
    }


    protected static function booted(): void
    {
        static::creating(function (VentaVencimiento $vencimiento) {
            if (!$vencimiento->vve_numero) {
                $vencimiento->vve_numero = (
                    static::where('ven_id', $vencimiento->ven_id)
                        ->max('vve_numero') ?? 0
                ) + 1;
            }
        });
    }

}
