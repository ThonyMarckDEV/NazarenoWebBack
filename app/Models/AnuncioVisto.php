<?php

// app/Models/AnuncioVisto.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnuncioVisto extends Model
{
    use HasFactory;

    protected $table = 'anuncios_vistos'; // Especifica la tabla asociada

    public $timestamps = false;

    protected $fillable = [
        'idAnuncio',
        'idAlumno'
    ];

    // Relación con el modelo AnuncioDocente
    public function anuncio()
    {
        return $this->belongsTo(AnuncioDocente::class, 'idAnuncio');
    }

    // Relación con el modelo Usuario para obtener al alumno
    public function alumno()
    {
        return $this->belongsTo(Usuario::class, 'idAlumno');
    }
}
