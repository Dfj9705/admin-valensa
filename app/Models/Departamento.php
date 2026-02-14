<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    use HasFactory;

    protected $table = 'departamentos';
    protected $primaryKey = 'dep_id';
    protected $fillable = [
        'dep_id',
        'dep_nombre',
        'dep_estado',
    ];

    public function municipios()
    {
        return $this->hasMany(Municipio::class, 'dep_id', 'dep_id');
    }

    public function clientes()
    {
        return $this->hasMany(Cliente::class, 'cli_departamento_id', 'dep_id');
    }
}
