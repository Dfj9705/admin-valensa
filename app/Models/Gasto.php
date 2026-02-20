<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    protected $table = 'gastos';
    protected $primaryKey = 'gas_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'gas_fecha',
        'gas_tipo',
        'gas_descripcion',
        'gas_referencia',
        'gas_monto',
        'cat_id',
        'creado_por',
    ];

    protected $casts = [
        'gas_fecha' => 'date',
        'gas_monto' => 'decimal:2',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'cat_id');
    }
}
