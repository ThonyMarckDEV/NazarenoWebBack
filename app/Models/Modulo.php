<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    use HasFactory;

    protected $table = 'modulos';
    protected $primaryKey = 'idModulo';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'idCurso',
    ];

     // Relación con Curso (un módulo pertenece a un curso)
     public function curso()
     {
         return $this->belongsTo(Curso::class, 'idCurso');
     }
}
