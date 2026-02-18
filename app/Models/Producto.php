<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use HasFactory;
    protected $primaryKey = 'pro_id';

    protected $fillable = [
        'pro_nombre',
        'pro_sku',
        'pro_descripcion',
        'pro_stock',
        'pro_precio_costo',
        'pro_precio_venta_min',
        'pro_precio_venta_max',
        'pro_imagenes',
        'pro_activo',
    ];

    protected $casts = [
        'pro_imagenes' => 'array',
        'pro_activo' => 'boolean',
    ];

    public function movimientos()
    {
        return $this->hasMany(ProductoMovimiento::class, 'pro_id', 'pro_id');
    }

    public function getProStockAttribute(): int
    {
        return (int) $this->movimientos()
            ->selectRaw("
            COALESCE(SUM(CASE WHEN mop_tipo = 'entrada' THEN mop_cantidad ELSE 0 END), 0)
            + 
            COALESCE(SUM(CASE WHEN mop_tipo = 'devolucion' THEN mop_cantidad ELSE 0 END), 0)
            -
            COALESCE(SUM(CASE WHEN mop_tipo = 'salida' THEN mop_cantidad ELSE 0 END), 0)
            -
            COALESCE(SUM(CASE WHEN mop_tipo = 'venta' THEN mop_cantidad ELSE 0 END), 0)
            AS stock
        ")
            ->value('stock') ?? 0;
    }
}
