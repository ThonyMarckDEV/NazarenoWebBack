<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlumnoMatriculado extends Model
{
    use HasFactory;

    protected $table = 'alumnosmatriculados';
    protected $primaryKey = 'idMatricula';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'idGrado',
        'fechaMatricula'
    ];

    // Relación con el modelo Usuario
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'idUsuario');
    }

    // Relación con el modelo Grado
    public function grado()
    {
        return $this->belongsTo(Grado::class, 'idGrado');
    }
}
