<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'cli_id';
    protected $fillable = [
        'cli_id',
        'cli_nombre',
        'cli_nombre_fel',
        'cli_email',
        'cli_telefono',
        'cli_nit',
        'cli_cui',
        'cli_direccion',
        'cli_departamento_id',
        'cli_municipio_id',
        'cli_activo',
        'cli_creado_por',
        'cli_actualizado_por',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'cli_departamento_id', 'dep_id');
    }

    public function municipio()
    {
        return $this->belongsTo(Municipio::class, 'cli_municipio_id', 'mun_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'cli_creado_por', 'id');
    }

    public function actualizador()
    {
        return $this->belongsTo(User::class, 'cli_actualizado_por', 'id');
    }
}
