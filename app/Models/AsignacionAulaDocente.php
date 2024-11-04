<?php

// app/Models/AsignacionAulaDocente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsignacionAulaDocente extends Model
{
    use HasFactory;

    protected $table = 'asignacion_aula_docente';
    protected $primaryKey = 'idAsignacion';
    protected $fillable = ['idDocente', 'idAula'];

    public $timestamps = false;


    // Relación con Docente
    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'idDocente');
    }

    // Relación con Aula (Grado)
    public function aula()
    {
        return $this->belongsTo(Grado::class, 'idAula');
    }
}
