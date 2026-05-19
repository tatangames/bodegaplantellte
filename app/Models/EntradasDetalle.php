<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradasDetalle extends Model
{
    use HasFactory;
    protected $table = 'entradas_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_entradas',
        'id_material',
        'cantidad_inicial',
        'precio',
        'codigo'
    ];

    public function material()
    {
        return $this->belongsTo(Materiales::class, 'id_material', 'id');
        // Ajusta 'id_material' según la FK real en entradas_detalle
    }

    public function entrada()
    {
        return $this->belongsTo(Entradas::class, 'id_entradas');
    }
}
