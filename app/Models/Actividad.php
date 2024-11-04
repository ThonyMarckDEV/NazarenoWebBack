<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Actividad extends Model
{
    use HasFactory;
    protected $table = 'actividades'; // Nombre correcto de la tabla
    // Define el nombre de la clave primaria
    protected $primaryKey = 'idActividad';
    protected $fillable = ['titulo', 'descripcion', 'fecha', 'fecha_vencimiento', 'idModulo'];
    public $timestamps = false;

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'idModulo');
    }
}
