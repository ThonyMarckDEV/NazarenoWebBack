<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TareaAlumno extends Model
{
    use HasFactory;

    protected $table = 'tareas_alumnos';

    protected $primaryKey = 'idTarea';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario', 
        'idActividad', 
        'nota', 
        'archivo_nombre', 
        'archivo_tipo', 
        'ruta', 
        'fecha_subida', 
        'revisado'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario');
    }

    public function actividad()
    {
        return $this->belongsTo(Actividad::class, 'idActividad');
    }
}
