<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Especialidad;
use App\Models\ActividadUsuario; // Asegúrate de importar el modelo correcto

class Usuario extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $table = 'usuarios';
    protected $primaryKey = 'idUsuario'; 
    public $timestamps = false;

    protected $fillable = [
        'username', 'rol', 'nombres', 'apellidos', 'dni', 'correo', 'edad', 'nacimiento', 'sexo', 'direccion', 'telefono', 'departamento', 'password', 'status', 'perfil',
    ];

    protected $hidden = ['password'];

    // JWT: Identificador del token
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        // Asegúrate de actualizar el estado antes de emitir el token
        $this->update(['status' => 'loggedOn']);

        return [
            'idUsuario' => $this->idUsuario,
            'dni' => $this->dni,
            'nombres' => $this->nombres,
            'estado' => $this->status, 
            'rol' => $this->rol,
        ];
    }

    public function especialidades()
    {
        return $this->belongsToMany(Especialidad::class, 'especialidad_docente', 'idDocente', 'idEspecialidad');
    }

    // Relación con ActividadUsuario
    public function activity()
    {
        return $this->hasOne(ActividadUsuario::class, 'idUsuario'); // Cambiado a ActividadUsuario
    }
}
