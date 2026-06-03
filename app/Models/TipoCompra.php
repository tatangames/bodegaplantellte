<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCompra extends Model
{
    use HasFactory;
    protected $table = 'tipo_compra';
    public $timestamps = false;
    protected $fillable = ['nombre'];
}
