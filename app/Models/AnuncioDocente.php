<?php

// app/Models/AnuncioDocente.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnuncioDocente extends Model
{
    use HasFactory;

    protected $table = 'anuncios_docente';

    public $timestamps = false;

    protected $fillable = [
        'nombreCurso',
        'seccion',
        'descripcion',
        'fecha',
        'hora',
        'idDocente',
    ];

    public function docente()
    {
        return $this->belongsTo(Usuario::class, 'idDocente');
    }
}
