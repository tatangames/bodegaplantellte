<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class Salidas extends Model
{
    use HasFactory;
    protected $table = 'salidas';
    public $timestamps = false;
    protected $fillable = ['id_equipo','fecha', 'descripcion', 'ficha_nombre', 'ficha_talonario'];


    public function detalle()
    {
        return $this->hasMany(SalidasDetalle::class, 'id_salida');
    }

    public function detalles()
    {
        return $this->hasMany(SalidasDetalle::class, 'id_salida', 'id');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipos::class, 'id_equipo');
    }

}
