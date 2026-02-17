<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaProducto extends Model
{
    use HasFactory;

    protected $table = 'venta_productos';

    protected $fillable = [
        'ven_id',
        'pro_id',
        'qty',
        'unit_price',
        'discount',
        'line_total',
        'description_snapshot',
        'uom_snapshot',
        'meta',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:4',
        'line_total' => 'decimal:2',
        'meta' => 'array',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class, 'ven_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'pro_id');
    }

}
