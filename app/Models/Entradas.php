<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Entradas extends Model
{
    use HasFactory;
    protected $table = 'entradas';
    public $timestamps = false;

    protected $fillable = [
        'id_tipoentrada',
        'id_tipocompra',
        'fecha',
        'descripcion',
        'factura',
    ];

    public function detalle()
    {
        return $this->hasMany(EntradasDetalle::class, 'id_entradas');
    }

    public function tipoEntrada()
    {
        return $this->belongsTo(TipoEntrada::class, 'id_tipoentrada');
    }

    public function tipoCompra()
    {
        return $this->belongsTo(TipoCompra::class, 'id_tipocompra');
    }
}
