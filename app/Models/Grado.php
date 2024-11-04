<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grado extends Model
{
    use HasFactory;

    protected $table = 'grados';
    protected $primaryKey = 'idGrado';
    public $timestamps = false;

    protected $fillable = [
        'nombreGrado',
        'nivel',
        'seccion',
        'cupos'
    ];

    // Relación con Curso
    public function cursos()
    {
        return $this->hasMany(Curso::class, 'idGrado');
    }
}
