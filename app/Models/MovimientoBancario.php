<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoBancario extends Model
{
    protected $table = 'movimientos_bancarios';

    protected $primaryKey = 'mov_id';

    protected $fillable = [
        'cue_id',
        'mov_fecha',
        'mov_tipo',
        'mov_concepto',
        'mov_monto',
        'mov_referencia',
        'mov_created_by',
    ];

    protected $casts = [
        'mov_fecha' => 'date',
        'mov_monto' => 'decimal:2',
    ];

    public function cuentaBancaria(): BelongsTo
    {
        return $this->belongsTo(
            CuentaBancaria::class,
            'cue_id',
            'cue_id'
        );
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'mov_created_by',
            'id'
        );
    }
}
