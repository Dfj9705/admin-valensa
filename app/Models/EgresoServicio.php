<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EgresoServicio extends Model
{
    use HasFactory;
    protected $table = 'egresos_servicios';
    protected $primaryKey = 'egr_id';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'egr_fecha',
        'egr_lugar',
        'cat_id',
        'egr_observaciones',
        'egr_monto',
        'egr_metodo_pago',
        'egr_referencia',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'egr_fecha' => 'date',
        'egr_monto' => 'decimal:2',
    ];
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'cat_id', 'cat_id');
    }
}
