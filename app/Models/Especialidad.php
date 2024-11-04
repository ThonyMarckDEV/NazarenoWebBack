<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Usuario;

class Especialidad extends Model
{
    use HasFactory;

    protected $table = 'especialidades';
    protected $primaryKey = 'idEspecialidad';
    public $timestamps = false;

    protected $fillable = [
        'nombreEspecialidad',
    ];

    // Relación con Curso
    public function cursos()
    {
        return $this->hasMany(Curso::class, 'idEspecialidad');
    }

    public function docentes()
    {
        return $this->belongsToMany(Usuario::class, 'especialidad_docente', 'idEspecialidad', 'idDocente');
    }

}
