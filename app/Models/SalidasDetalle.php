<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalidasDetalle extends Model
{
    use HasFactory;
    protected $table = 'salidas_detalle';
    public $timestamps = false;
    protected $fillable = ['id_salida', 'id_entrada_detalle', 'cantidad_salida'];

    public function entradaDetalle()
    {
        return $this->belongsTo(EntradasDetalle::class, 'id_entrada_detalle', 'id');
    }

    public function salida()
    {
        return $this->belongsTo(Salidas::class, 'id_salida');
    }

    public function material()
    {
        return $this->belongsTo(Materiales::class, 'id_material', 'id');
        // Ajusta: Material::class  → nombre real de tu modelo
        //         'id_material'    → FK en salidasdetalle
        //         'id'             → PK en la tabla materiales
    }


}
