<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductoMovimiento extends Model
{
    use HasFactory;
    protected $table = 'movimientos_productos';
    protected $primaryKey = 'mop_id';

    protected $fillable = [
        'pro_id',
        'mop_tipo',
        'mop_cantidad',
        'mop_costo_unitario',
        'mop_referencia_tipo',
        'mop_referencia_id',
        'mop_observacion',
        'mop_fecha',
    ];

    protected $casts = [
        'mop_fecha' => 'datetime',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'pro_id', 'pro_id');
    }
}
