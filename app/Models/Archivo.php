<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Archivo extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'tipo', 'ruta', 'idModulo'];
    public $timestamps = false;

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'idModulo');
    }
}


