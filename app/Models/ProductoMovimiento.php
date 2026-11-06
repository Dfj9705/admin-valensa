<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw \Illuminate\Validation\ValidationException::withMessages(['movimiento' => 'El historial es inmutable. Registra un movimiento de compensación.']);
        }
        return \Illuminate\Support\Facades\DB::transaction(function () use ($options) {
            $producto = Producto::whereKey($this->pro_id)->lockForUpdate()->firstOrFail();
            if (!in_array($this->mop_tipo, ['entrada', 'devolucion', 'salida', 'venta'], true) || !is_numeric($this->mop_cantidad) || $this->mop_cantidad <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['mop_cantidad' => 'Tipo o cantidad de movimiento inválidos.']);
            }
            if (in_array($this->mop_tipo, ['salida', 'venta'], true)) {
                // A current locking read avoids an older REPEATABLE READ snapshot.
                $stock = $producto->movimientos()->lockForUpdate()->get(['mop_tipo', 'mop_cantidad'])
                    ->sum(fn($m) => in_array($m->mop_tipo, ['entrada', 'devolucion'], true) ? $m->mop_cantidad : -$m->mop_cantidad);
                if ($this->mop_cantidad > $stock) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['mop_cantidad' => "Stock insuficiente. Disponible: {$stock}."]);
                }
            }
            return parent::save($options);
        });
    }

    protected static function booted(): void
    {
        static::deleting(function () {
            throw \Illuminate\Validation\ValidationException::withMessages(['movimiento' => 'El historial es inmutable. Registra un movimiento de compensación.']);
        });

        static::creating(function (ProductoMovimiento $movimiento) {
            if (auth()->check()) {
                $movimiento->mop_created_by = auth()->id();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mop_created_by', 'id');
    }

}
