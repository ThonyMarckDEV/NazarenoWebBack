<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;
use App\Models\Especialidad;

class EspecialidadDocente extends Model
{
    use HasFactory;

    protected $table = 'especialidad_docente';
    protected $primaryKey = 'id';

    public $timestamps = false;

     // Relación con el modelo Usuario (docente)
    public function usuario() // Cambiado a "usuario" para que coincida con la llamada en el código
    {
        return $this->belongsTo(Usuario::class, 'idDocente', 'idUsuario'); // 'idDocente' se refiere al usuario con rol docente
    }

    public function especialidad()
    {
        return $this->belongsTo(Especialidad::class, 'idEspecialidad', 'idEspecialidad');
    }

    // Definir la relación con el modelo Curso
    public function curso()
    {
        return $this->belongsTo(Curso::class, 'idCurso');
    }
}


