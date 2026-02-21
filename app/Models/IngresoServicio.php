<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngresoServicio extends Model
{
    use HasFactory;

    protected $table = 'ingresos_servicios';
    protected $primaryKey = 'ing_id';
    public $incrementing = true;
    public $timestamps = true;

    protected $fillable = [
        'ing_fecha',
        'ing_lugar',
        'ing_observaciones',
        'ing_monto',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'ing_fecha' => 'date',
        'ing_monto' => 'decimal:2',
    ];
}
