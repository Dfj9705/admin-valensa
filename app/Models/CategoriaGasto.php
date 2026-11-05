<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaGasto extends Model
{
    use HasFactory;

    protected $table = 'categorias_gastos';
    protected $primaryKey = 'cat_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cat_nombre',
        'cat_activo',
    ];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'cat_id');
    }

    protected static function booted(): void
    {
        static::deleting(function ($record) {
            if (!(!$record->gastos()->exists() && !\App\Models\EgresoServicio::where('cat_id', $record->getKey())->exists())) {
                throw \Illuminate\Validation\ValidationException::withMessages(['registro' => 'Este registro tiene historial asociado. Desactívalo en lugar de eliminarlo.']);
            }
        });
    }

}
