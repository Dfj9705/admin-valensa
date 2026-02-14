<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    use HasFactory;

    protected $table = 'municipios';
    protected $primaryKey = 'mun_id';
    protected $fillable = [
        'mun_id',
        'dep_id',
        'mun_nombre',
        'mun_estado',
    ];

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'dep_id', 'dep_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'cli_municipio_id', 'mun_id');
    }
}
