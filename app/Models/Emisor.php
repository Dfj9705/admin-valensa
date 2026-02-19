<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Emisor extends Model
{
    use HasFactory;

    protected $table = 'emisores';
    protected $primaryKey = 'emi_id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'emi_nit',
        'emi_nombre_emisor',
        'emi_codigo_establecimiento',
        'emi_nombre_comercial',
        'emi_correo_emisor',
        'emi_afiliacion_iva',
        'emi_direccion',
        'emi_codigo_postal',
        'emi_municipio',
        'emi_departamento',
        'emi_pais',
        'emi_frase_tipo',
        'emi_frase_escenario',
        'emi_frase_texto',
        'emi_tekra_usuario',
        'emi_tekra_clave',
        'emi_tekra_cliente',
        'emi_tekra_contrato',
        'emi_activo',
    ];

    protected $casts = [
        'emi_activo' => 'boolean',
    ];

    public function ventas()
    {
        return $this->hasMany(Venta::class, 'ven_emisor_id', 'emi_id');
    }


}
