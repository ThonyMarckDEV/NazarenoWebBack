<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Especialidad;
use App\Models\Grado;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\AlumnoMatriculado;
use App\Models\EspecialidadDocente;
use App\Models\AsignacionAulaDocente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{

    // FUNCION PARA REGISTRAR UN USUARIO
    public function register(Request $request)
    {
        $messages = [
            'username.required' => 'El nombre de usuario es obligatorio.',
            'username.unique' => 'El nombre de usuario ya está en uso.',
            'rol.required' => 'El rol es obligatorio.',
            'nombres.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',
            'apellidos.regex' => 'Debe ingresar al menos dos apellidos separados por un espacio.',
            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 caracteres.',
            'dni.unique' => 'El DNI ya está registrado.',
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe tener un formato válido.',
            'correo.unique' => 'El correo ya está registrado.',
            'edad.integer' => 'La edad debe ser un número entero.',
            'edad.between' => 'La edad debe estar entre 1 y 100 años.',
            'nacimiento.date' => 'La fecha de nacimiento debe ser una fecha válida.',
            'nacimiento.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    
        $validator = Validator::make($request->all(), [
            // Reglas de validación
            'username' => 'required|string|max:255|unique:usuarios',
            'rol' => 'required|string|max:255',
            'nombres' => 'required|string|max:255',
            'apellidos' => [
                'required',
                'regex:/^[a-zA-ZÀ-ÿ]+(\s[a-zA-ZÀ-ÿ]+)+$/'
            ],
            'dni' => 'required|string|size:8|unique:usuarios',
            'correo' => 'required|string|email|max:255|unique:usuarios',
            'edad' => 'nullable|integer|between:1,100',
            'nacimiento' => 'nullable|date|before:today',
            'telefono' => 'nullable|string|max:9',
            'departamento' => 'nullable|string|max:255',
            'perfil' => 'nullable|string|max:255',
            'password' => 'required|string|min:6|confirmed',
        ], $messages);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }
    
        try {
            // Manejar campos opcionales con valores predeterminados
            $user = Usuario::create([
                'username' => $request->username,
                'rol' => $request->rol,
                'nombres' => $request->nombres,
                'apellidos' => $request->apellidos,
                'dni' => $request->dni,
                'correo' => $request->correo,
                'edad' => $request->edad ?? null,
                'nacimiento' => $request->nacimiento ?? null,
                'telefono' => $request->telefono ?? null,
                'departamento' => $request->departamento ?? null,
                'password' => bcrypt($request->password),
                'status' => 'loggedOff',
                'perfil' => $request->perfil ?? null,
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
            ], 201);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar el usuario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Función para agregar una especialidad// Función para agregar una especialidad
    public function agregarEspecialidad(Request $request)
    {
        // Validar los datos
        $validator = Validator::make($request->all(), [
            'nombreEspecialidad' => 'required|string|max:255|unique:especialidades,nombreEspecialidad'
        ]);

        if ($validator->fails()) {
            $errorMessage = $validator->errors()->first('nombreEspecialidad') === 'The nombre especialidad has already been taken.'
                ? 'La especialidad ya existe'
                : 'Error en la validación';

            return response()->json([
                'success' => false,
                'message' => $errorMessage,
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $especialidad = Especialidad::create([
                'nombreEspecialidad' => $request->nombreEspecialidad,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Especialidad agregada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la especialidad',
                'error' => $e->getMessage()
            ], 500);
        }
    }


   // FUNCION PARA AGREGAR CURSO
    public function agregarCurso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombreCurso' => 'required|string|max:255',
            'idEspecialidad' => 'required|exists:especialidades,idEspecialidad',
            'idGrado' => 'required|exists:grados,idGrado',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()], 400);
        }

        // Verificar si el curso ya existe en el mismo grado y sección
        $cursoExistente = Curso::where('nombreCurso', $request->nombreCurso)
            ->where('idGrado', $request->idGrado)
            ->first();

        if ($cursoExistente) {
            return response()->json([
                'success' => false,
                'message' => 'El curso ya está registrado en este grado y sección'
            ], 400);
        }

        // Crear el curso
        $curso = Curso::create([
            'nombreCurso' => $request->nombreCurso,
            'idEspecialidad' => $request->idEspecialidad,
            'idGrado' => $request->idGrado,
        ]);

        // Crear los 6 módulos por defecto para el curso
        $modulos = ['Módulo 1', 'Módulo 2', 'Módulo 3', 'Módulo 4', 'Módulo 5', 'Módulo 6'];
        foreach ($modulos as $nombreModulo) {
            Modulo::create([
                'nombre' => $nombreModulo,
                'idCurso' => $curso->idCurso,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Curso y módulos registrados exitosamente'], 201);
    }

    //Funcion para listar Especialidades
    public function listarEspecialidades()
    {
        $especialidades = Especialidad::select('idEspecialidad', 'nombreEspecialidad')->get();
        return response()->json([
            'success' => true,
            'data' => $especialidades
        ], 200);
    }

    //Funcion para listar Grados
    public function listarGrados()
    {
        $grados = Grado::select('idGrado', 'nombreGrado','seccion')->get();
        return response()->json([
            'success' => true,
            'data' => $grados
        ], 200);
    }


    // Listar usuarios
    public function listarUsuarios()
    {
        $usuarios = Usuario::select('idUsuario', 'username', 'rol', 'correo')
                    ->where('rol', '!=', 'admin') // Excluir usuarios con rol "admin"
                    ->get();
        return response()->json(['success' => true, 'data' => $usuarios]);
    }

    // Eliminar usuario
    public function eliminarUsuario($id)
    {
        $usuario = Usuario::find($id);
        if ($usuario) {
            $usuario->delete();
            return response()->json(['success' => true, 'message' => 'Usuario eliminado correctamente']);
        }
        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }

    // Actualizar usuario
    public function actualizarUsuario(Request $request, $id)
    {
        $usuario = Usuario::find($id);
        if ($usuario) {
            $usuario->update($request->only('username', 'rol', 'correo'));
            return response()->json(['success' => true, 'message' => 'Usuario actualizado correctamente']);
        }
        return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
    }


    public function listarEstudiantes()
    {
        $estudiantes = Usuario::select('idUsuario', DB::raw("CONCAT(nombres, ' ', apellidos) as nombre_completo"))
            ->where('rol', 'estudiante')
            ->get();

        return response()->json(['success' => true, 'data' => $estudiantes]);
    }


    public function listarGradosCupos()
    {
        $grados = Grado::select('idGrado', 'nombreGrado', 'seccion', 'cupos')->get();
        return response()->json(['success' => true, 'data' => $grados]);
    }

    public function matricularEstudiante(Request $request)
    {
        $idUsuario = $request->input('idUsuario');
        $idGrado = $request->input('idGrado');

        $grado = Grado::find($idGrado);
        if (!$grado || $grado->cupos <= 0) {
            return response()->json(['success' => false, 'message' => 'No hay cupos disponibles.']);
        }

        $matriculaExistente = AlumnoMatriculado::where('idUsuario', $idUsuario)->exists();
        if ($matriculaExistente) {
            return response()->json(['success' => false, 'message' => 'El estudiante ya está matriculado.']);
        }

        AlumnoMatriculado::create([
            'idUsuario' => $idUsuario,
            'idGrado' => $idGrado
        ]);

        $grado->update(['cupos' => $grado->cupos - 1]);
        return response()->json(['success' => true, 'message' => 'Estudiante matriculado exitosamente.']);
    }


    // Listar estudiantes matriculados
  
    public function listarMatriculas()
    {
        $matriculas = AlumnoMatriculado::with([
            'usuario:idUsuario',
            'grado:idGrado,nombreGrado,seccion' // Incluimos 'seccion' en la relación
        ])
        ->join('usuarios', 'usuarios.idUsuario', '=', 'alumnosmatriculados.idUsuario')
        ->select(
            'alumnosmatriculados.idMatricula',
            'alumnosmatriculados.idUsuario',
            'alumnosmatriculados.idGrado',
            'alumnosmatriculados.fechaMatricula',
            DB::raw("CONCAT(usuarios.nombres, ' ', usuarios.apellidos) as nombre_completo")
        )
        ->get();
    
        return response()->json(['success' => true, 'data' => $matriculas]);
    }

    // Eliminar matrícula
    public function eliminarMatricula($idMatricula)
    {
        $matricula = AlumnoMatriculado::find($idMatricula);
        
        if (!$matricula) {
            return response()->json(['success' => false, 'message' => 'Matrícula no encontrada.'], 404);
        }

        // Incrementar el cupo del grado
        $grado = Grado::find($matricula->idGrado);
        $grado->increment('cupos');

        // Eliminar la matrícula
        $matricula->delete();

        return response()->json(['success' => true, 'message' => 'Matrícula eliminada exitosamente.']);
    }


    public function listarDocentes()
    {
        // Obtener solo los docentes y concatenar nombres y apellidos
        $docentes = Usuario::where('rol', 'docente')
            ->get(['idUsuario', 'nombres', 'apellidos'])
            ->map(function ($docente) {
                return [
                    'idUsuario' => $docente->idUsuario,
                    'nombreCompleto' => $docente->nombres . ' ' . $docente->apellidos, // Concatenar nombres y apellidos
                ];
            });
    
        // Asegurar que la respuesta JSON esté bajo 'data'
        return response()->json(['data' => $docentes]);
    }

    public function asignarEspecialidadDocente(Request $request)
    {
        $request->validate([
            'idEspecialidad' => 'required|exists:especialidades,idEspecialidad',
            'idDocente' => 'required|exists:usuarios,idUsuario'
        ]);
    
        $especialidad = Especialidad::find($request->idEspecialidad);
    
        // Verificar si el docente ya tiene asignada esta especialidad
        if ($especialidad->docentes()->where('idUsuario', $request->idDocente)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'El docente ya tiene esta especialidad asignada.'
            ], 400);
        }
    
        // Asignar la especialidad al docente
        $especialidad->docentes()->attach($request->idDocente);
    
        return response()->json(['success' => true, 'message' => 'Especialidad asignada al docente exitosamente.']);
    }
    

    // Listar asignaciones de especialidades a docentes
    public function listarAsignaciones()
    {
        $asignaciones = EspecialidadDocente::with(['usuario', 'especialidad'])->get();

        $asignacionesData = $asignaciones->map(function ($asignacion) {
            return [
                'id' => $asignacion->id,
                'docente' => $asignacion->usuario->nombres . ' ' . $asignacion->usuario->apellidos, // Concatenar nombres y apellidos
                'especialidad' => $asignacion->especialidad->nombreEspecialidad,
            ];
        });

        return response()->json(['success' => true, 'data' => $asignacionesData]);
    }

    // Eliminar una asignación específica
    public function eliminarAsignacion($id)
    {
        $asignacion = EspecialidadDocente::find($id);

        if (!$asignacion) {
            return response()->json(['success' => false, 'message' => 'Asignación no encontrada.']);
        }

        $asignacion->delete();

        return response()->json(['success' => true, 'message' => 'Asignación eliminada exitosamente.']);
    }

    public function eliminarEspecialidad($idEspecialidad)
    {
        try {
            $especialidad = Especialidad::find($idEspecialidad);
            
            if (!$especialidad) {
                return response()->json(['success' => false, 'message' => 'Especialidad no encontrada'], 404);
            }

            $especialidad->delete();

            return response()->json(['success' => true, 'message' => 'Especialidad eliminada exitosamente'], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al eliminar la especialidad', 'error' => $e->getMessage()], 500);
        }
    }

    public function listarCursos()
    {
        $cursos = Curso::with(['especialidad', 'grado'])->get();

        return response()->json([
            'success' => true,
            'data' => $cursos
        ]);
    }

    public function eliminarCurso($idCurso)
    {
        $curso = Curso::find($idCurso);

        if (!$curso) {
            return response()->json([
                'success' => false,
                'message' => 'Curso no encontrado'
            ], 404);
        }

        $curso->delete();

        return response()->json([
            'success' => true,
            'message' => 'Curso eliminado exitosamente'
        ]);
    }


    // Función para asignar un aula a un docente
    public function asignarAulaDocente(Request $request)
    {
        $request->validate([
            'idDocente' => 'required|exists:usuarios,idUsuario',
            'idAula' => 'required|exists:grados,idGrado',
        ]);

        // Verificar si ya existe la asignación para este docente y aula
        $existsForDocente = AsignacionAulaDocente::where('idDocente', $request->idDocente)
            ->where('idAula', $request->idAula)
            ->exists();

        if ($existsForDocente) {
            return response()->json([
                'success' => false,
                'message' => 'El docente ya está asignado a esta aula.'
            ], 400);
        }

        // Verificar si otro docente ya está asignado a esta aula
        $existsForAula = AsignacionAulaDocente::where('idAula', $request->idAula)->exists();

        if ($existsForAula) {
            return response()->json([
                'success' => false,
                'message' => 'Esta aula ya está asignada a otro docente.'
            ], 400);
        }

        // Crear la nueva asignación
        $asignacion = AsignacionAulaDocente::create([
            'idDocente' => $request->idDocente,
            'idAula' => $request->idAula
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Aula asignada exitosamente.',
            'data' => $asignacion
        ]);
    }


    // app/Http/Controllers/DocenteController.php
    public function listarTodasLasAsignaciones()
    {
        $asignaciones = AsignacionAulaDocente::with(['docente', 'aula'])->get()
            ->map(function ($asignacion) {
                return [
                    'idAsignacion' => $asignacion->idAsignacion,
                    'nombreDocente' => $asignacion->docente->nombres . ' ' . $asignacion->docente->apellidos,
                    'nombreAula' => $asignacion->aula->nombreGrado,
                    'seccion' => $asignacion->aula->seccion,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $asignaciones
        ]);
    }

    // Función para eliminar una asignación
    public function eliminarAsignacionAulaDocente($idAsignacion)
    {
        $asignacion = AsignacionAulaDocente::find($idAsignacion);
        
        if (!$asignacion) {
            return response()->json([
                'success' => false,
                'message' => 'Asignación no encontrada.'
            ], 404);
        }

        $asignacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asignación eliminada exitosamente.'
        ]);
    }

    //FUNCIONES PARA REPORTES

    public function listarGradosReportes()
    {
        return response()->json(Grado::select('idGrado', 'nombreGrado', 'nivel', 'seccion')->get());
    }

    public function getAlumnosByGrado($idGrado)
    {
        $alumnos = AlumnoMatriculado::where('idGrado', $idGrado)
            ->join('usuarios', 'alumnosmatriculados.idUsuario', '=', 'usuarios.idUsuario')
            ->select('usuarios.idUsuario', 'usuarios.nombres', 'usuarios.apellidos', 'usuarios.perfil', 'usuarios.rol')
            ->where('usuarios.rol', 'estudiante')
            ->get();

        return response()->json($alumnos);
    }

    public function notasPorGrado($idGrado)
    {
        $notas = DB::table('usuarios as u')
            ->join('tareas_alumnos as ta', 'u.idUsuario', '=', 'ta.idUsuario')
            ->join('actividades as a', 'ta.idActividad', '=', 'a.idActividad')
            ->join('modulos as m', 'a.idModulo', '=', 'm.idModulo')
            ->join('cursos as c', 'm.idCurso', '=', 'c.idCurso')
            ->select(
                'u.nombres',
                'u.apellidos',
                'u.perfil as foto_perfil', // Añadir esta línea
                'a.titulo as titulo_actividad',
                'ta.nota',
                'ta.fecha_subida',
                'a.fecha_vencimiento',
                'c.nombreCurso as curso'
            )
            ->where('u.rol', 'estudiante')
            ->where('c.idGrado', $idGrado)
            ->get();
    
        return response()->json($notas);
    }

    public function cursosPorAlumno($idUsuario, $idGrado)
    {
        $cursos = DB::table('cursos as c')
            ->join('alumnosmatriculados as am', 'c.idGrado', '=', 'am.idGrado')
            ->select('c.idCurso', 'c.nombreCurso')
            ->where('am.idUsuario', $idUsuario)
            ->where('am.idGrado', $idGrado)
            ->get();

        return response()->json($cursos);
    }

     // Obtener notas del alumno por curso
     public function notasPorAlumnoYCurso($idUsuario, $idCurso)
     {
         $notas = DB::table('usuarios as u')
             ->join('tareas_alumnos as ta', 'u.idUsuario', '=', 'ta.idUsuario')
             ->join('actividades as a', 'ta.idActividad', '=', 'a.idActividad')
             ->join('modulos as m', 'a.idModulo', '=', 'm.idModulo')
             ->join('cursos as c', 'm.idCurso', '=', 'c.idCurso')
             ->select(
                 'u.nombres',
                 'u.apellidos',
                 'u.perfil as foto_perfil',
                 'a.titulo as titulo_actividad',
                 'ta.nota',
                 'ta.fecha_subida',
                 'a.fecha_vencimiento',
                 'c.nombreCurso as curso'
             )
             ->where('u.idUsuario', $idUsuario)
             ->where('u.rol', 'estudiante')
             ->where('c.idCurso', $idCurso)
             ->get();
 
         return response()->json($notas);
     }
 
 
      // Obtener notas generales del alumno
    public function notasPorAlumno($idUsuario)
    {
        $notas = DB::table('usuarios as u')
            ->join('tareas_alumnos as ta', 'u.idUsuario', '=', 'ta.idUsuario')
            ->join('actividades as a', 'ta.idActividad', '=', 'a.idActividad')
            ->join('modulos as m', 'a.idModulo', '=', 'm.idModulo')
            ->join('cursos as c', 'm.idCurso', '=', 'c.idCurso')
            ->select(
                'u.nombres',
                'u.apellidos',
                'u.perfil as foto_perfil',
                'a.titulo as titulo_actividad',
                'ta.nota',
                'ta.fecha_subida',
                'a.fecha_vencimiento',
                'c.nombreCurso as curso'
            )
            ->where('u.idUsuario', $idUsuario)
            ->where('u.rol', 'estudiante')
            ->get();

        return response()->json($notas);
    }
    
}
