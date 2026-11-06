<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Banco extends Model
{
    protected $table = 'bancos';
    protected $primaryKey = 'ban_id';

    protected $fillable = [
        'ban_nombre',
        'ban_activo',
    ];

    protected $casts = [
        'ban_activo' => 'boolean',
    ];

    public function cuentas(): HasMany
    {
        return $this->hasMany(
            CuentaBancaria::class,
            'ban_id',
            'ban_id'
        );
    }
}
