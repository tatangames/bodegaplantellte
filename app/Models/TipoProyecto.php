<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoProyecto extends Model
{
    use HasFactory;
    protected $table = 'tipoproyecto';
    public $timestamps = false;

    protected $casts = [
        'transferido' => 'boolean',
    ];

    public function transferencia()
    {
        return $this->hasMany(Transferencia::class, 'id_tipoproyecto');
    }


}
