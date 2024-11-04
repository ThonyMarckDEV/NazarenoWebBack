<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Curso extends Model
{
    use HasFactory;

    protected $table = 'cursos';
    protected $primaryKey = 'idCurso';

    public $timestamps = false;

    protected $fillable = [
        'nombreCurso',
        'idEspecialidad',
        'idGrado',
    ];

     // Relación con la Especialidad
     public function especialidad()
     {
         return $this->belongsTo(Especialidad::class, 'idEspecialidad');
     }
 
     // Relación con el Grado
     public function grado()
     {
         return $this->belongsTo(Grado::class, 'idGrado');
     }
 
     // Relación con Modulo (un curso tiene muchos módulos)
     public function modulos()
     {
         return $this->hasMany(Modulo::class, 'idCurso');
     }
     
}
