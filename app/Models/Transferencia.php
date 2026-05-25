<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transferencia extends Model
{
    use HasFactory;
    protected $table = 'transferencia';
    public $timestamps = false;

    protected $fillable = ['id_tipoproyecto', 'fecha', 'descripcion', 'documento', 'tipo_salida'];


    public function tipoproyecto()
    {
        return $this->belongsTo(TipoProyecto::class, 'id_tipoproyecto');
    }

    public function detalle()
    {
        return $this->hasMany(TransferenciaDetalle::class, 'id_transferencia');
    }

    // Proyecto ORIGEN (de donde vino el material - proyecto cerrado)
    public function tipoproyectoOrigen()
    {
        return $this->belongsTo(TipoProyecto::class, 'id_tipoproyecto_origen');
    }

    // Salida generada en el retiro
    public function salida()
    {
        return $this->belongsTo(Salidas::class, 'id_salida');
    }

    // Entrada generada en el retiro (solo en transferencia a proyecto)
    public function entrada()
    {
        return $this->belongsTo(Entradas::class, 'id_entrada');
    }


}



