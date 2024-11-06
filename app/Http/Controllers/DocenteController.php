<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use App\Models\AsignacionAulaDocente;
use App\Models\AnuncioDocente;
use App\Models\EspecialidadDocente;
use App\Models\Modulo;
use App\Models\TareaAlumno;
use App\Models\Actividad;
use App\Models\Archivo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class DocenteController extends Controller
{
// En DocenteController.php
public function perfilDocente()
{
        $usuario = Auth::user();
        $profileUrl = $usuario->perfil ? url("storage/{$usuario->perfil}") : null;

        return response()->json([
            'success' => true,
            'data' => [
                'idUsuario' => $usuario->idUsuario,
                'username' => $usuario->username,
                'nombres' => $usuario->nombres,
                'apellidos' => $usuario->apellidos,
                'dni' => $usuario->dni,
                'correo' => $usuario->correo,
                'edad' => $usuario->edad,
                'nacimiento' => $usuario->nacimiento,
                'sexo' => $usuario->sexo,
                'direccion' => $usuario->direccion,
                'telefono' => $usuario->telefono,
                'departamento' => $usuario->departamento,
                'perfil' => $profileUrl,  // URL completa de la imagen de perfil
            ]
        ]);
    }

    public function uploadProfileImageDocente(Request $request, $idUsuario)
    {
        $docente = Usuario::find($idUsuario);
        if (!$docente) {
            return response()->json(['success' => false, 'message' => 'Usuario no encontrado'], 404);
        }

        // Verifica si hay un archivo en la solicitud
        if ($request->hasFile('perfil')) {
            $path = "profiles/$idUsuario";

            // Si hay una imagen de perfil existente, elimínala antes de guardar la nueva
            if ($docente->perfil && Storage::disk('public')->exists($docente->perfil)) {
                Storage::disk('public')->delete($docente->perfil);
            }

            // Guarda la nueva imagen de perfil en el disco 'public'
            $filename = $request->file('perfil')->store($path, 'public');
            $docente->perfil = $filename; // Actualiza la ruta en el campo `perfil` del usuario
            $docente->save();

            return response()->json(['success' => true, 'filename' => basename($filename)]);
        }

        return response()->json(['success' => false, 'message' => 'No se cargó la imagen'], 400);
    }

    public function updateDocente(Request $request, $idUsuario)
    {
        $docente = Usuario::find($idUsuario);
        if (!$docente || $docente->rol !== 'docente') {
            return response()->json(['success' => false, 'message' => 'Docente no encontrado'], 404);
        }

        $docente->update($request->only([
            'nombres', 'apellidos', 'dni', 'correo', 'edad', 'nacimiento',
            'sexo', 'direccion', 'telefono', 'departamento'
        ]));

        return response()->json(['success' => true, 'message' => 'Datos actualizados correctamente']);
    }


   // Controlador DocenteController.php

   public function listarCursosPorDocente($idDocente)
   {
       // Obtener las especialidades asignadas al docente
       $especialidades = EspecialidadDocente::where('idDocente', $idDocente)->pluck('idEspecialidad');
   
       // Obtener los cursos y el grado-sección (aula) asignados al docente, filtrando por las especialidades del docente
       $cursos = AsignacionAulaDocente::where('asignacion_aula_docente.idDocente', $idDocente)
           ->join('especialidad_docente', 'especialidad_docente.idDocente', '=', 'asignacion_aula_docente.idDocente')
           ->join('cursos', function ($join) use ($especialidades) {
               $join->on('asignacion_aula_docente.idAula', '=', 'cursos.idGrado')
                    ->whereIn('cursos.idEspecialidad', $especialidades); // Filtrar especialidades asignadas al docente
           })
           ->join('grados', 'cursos.idGrado', '=', 'grados.idGrado')
           ->select('cursos.idCurso', 'cursos.nombreCurso', 'grados.nombreGrado', 'grados.seccion')
           ->distinct() // Eliminar duplicados
           ->get();
   
       // Formatear el resultado en un arreglo organizado
       $result = $cursos->map(function ($curso) {
           return [
               'idCurso' => $curso->idCurso,
               'nombreCurso' => $curso->nombreCurso,
               'nombreGrado' => $curso->nombreGrado,
               'seccion' => $curso->seccion,
           ];
       });
   
       // Retornar el resultado en formato JSON
       return response()->json([
           'success' => true,
           'data' => $result
       ]);
   }    

    public function store(Request $request)
    {
        $request->validate([
            'nombreCurso' => 'required|string|max:100',
            'seccion' => 'required|string|max:1',
            'descripcion' => 'required|string',
            'idDocente' => 'required|exists:usuarios,idUsuario',
        ]);

        // Asignar fecha y hora actuales
        $anuncio = AnuncioDocente::create([
            'nombreCurso' => $request->nombreCurso,
            'seccion' => $request->seccion,
            'descripcion' => $request->descripcion,
            'fecha' => Carbon::now()->toDateString(),
            'hora' => Carbon::now()->toTimeString(),
            'idDocente' => $request->idDocente,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Anuncio creado exitosamente',
            'data' => $anuncio
        ], 201);
    }

    public function listarModulosPorCurso($idCurso)
    {
        $modulos = Modulo::where('idCurso', $idCurso)->get();

        return response()->json([
            'success' => true,
            'data' => $modulos
        ]);
    }

    public function agregarActividad(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string|max:999',
            'fecha' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha',
            'idModulo' => 'required|exists:modulos,idModulo'
        ]);
    
        $actividad = Actividad::create($request->all());
    
        return response()->json([
            'success' => true,
            'message' => 'Actividad asignada correctamente',
            'data' => $actividad
        ], 201);
    }

    public function agregarArchivo(Request $request)
    {
        // Validación de los datos recibidos
        $request->validate([
            'nombre' => 'required|string|max:255',
            'archivo' => 'required|file|max:51200',
            'idModulo' => 'required|exists:modulos,idModulo'
        ]);
    
        try {
            // Buscar módulo y curso
            $modulo = Modulo::where('idModulo', $request->idModulo)->firstOrFail();
            $curso = $modulo->curso;
            
            // Verificar que el curso y el grado existan
            if (!$curso || !$curso->grado) {
                return response()->json(['success' => false, 'message' => 'Curso o grado no encontrados'], 404);
            }
    
            // Definir la ruta del directorio dentro de 'material'
            $rutaDirectorio = "material/{$curso->nombreCurso}-{$curso->grado->seccion}/{$modulo->nombre}";
    
            // Verificar archivo existente y eliminar si es necesario
            $archivoExistente = Archivo::where('idModulo', $request->idModulo)->where('nombre', $request->nombre)->first();
            if ($archivoExistente && Storage::disk('public')->exists($archivoExistente->ruta)) {
                Storage::disk('public')->delete($archivoExistente->ruta);
                $archivoExistente->delete();
            }
    
            // Almacenar el archivo en el directorio 'material' en el sistema de archivos
            $rutaArchivoCompleta = $request->file('archivo')->storeAs($rutaDirectorio, $request->file('archivo')->getClientOriginalName(), 'public');
    
            // Quitar 'material/' de la ruta para almacenar en la base de datos
            $rutaArchivo = str_replace('material/', '', $rutaArchivoCompleta);
    
            // Crear registro del archivo en la base de datos con la ruta sin 'material/'
            $archivo = Archivo::create([
                'nombre' => $request->nombre,
                'tipo' => $request->file('archivo')->getClientMimeType(),
                'ruta' => $rutaArchivo,
                'idModulo' => $request->idModulo
            ]);
    
            return response()->json([
                'success' => true,
                'message' => 'Material agregado correctamente',
                'data' => $archivo
            ], 201);
        } catch (\Exception $e) {
            // Capturar errores inesperados y devolver un mensaje claro
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function obtenerTareas($idModulo)
    {
        $tareas = TareaAlumno::with(['usuario:idUsuario,nombres,apellidos', 'actividad'])
            ->where('revisado', 'no') // Filtrar solo tareas no revisadas
            ->whereHas('actividad', function ($query) use ($idModulo) {
                $query->where('idModulo', $idModulo);
            })
            ->get()
            ->map(function ($tarea) {
                $tarea->usuario->nombre_completo = "{$tarea->usuario->nombres} {$tarea->usuario->apellidos}";
                return $tarea;
            });
    
        // Log para verificar los datos con idTarea incluido
        Log::info('Datos de tareas no revisadas obtenidas:', $tareas->toArray());
    
        return response()->json(['success' => true, 'tareas' => $tareas]);
    }
    
    
    public function revisarTarea(Request $request)
    {
        // Log para verificar los datos recibidos en la solicitud
        Log::info('Datos recibidos en revisarTarea:', $request->all());
    
        $request->validate([
            'idTarea' => 'required|exists:tareas_alumnos,idTarea',
            'nota' => 'required|numeric|min:0|max:20'
        ]);
    
        try {
            $tarea = TareaAlumno::findOrFail($request->idTarea);
            $tarea->nota = $request->nota;
            $tarea->revisado = 'si';
            $tarea->save();
    
            return response()->json(['success' => true, 'message' => 'Tarea revisada correctamente']);
        } catch (\Exception $e) {
            // Log para capturar el error si ocurre alguno
            Log::error('Error al revisar tarea:', ['error' => $e->getMessage()]);
            
            return response()->json(['success' => false, 'message' => 'Error al revisar la tarea', 'error' => $e->getMessage()], 500);
        }
    }


    public function obtenerTareasPendientesPorDocente($idDocente)
    {
        $tareasPendientes = DB::table('tareas_alumnos AS t')
            ->join('actividades AS a', 't.idActividad', '=', 'a.idActividad')
            ->join('modulos AS m', 'a.idModulo', '=', 'm.idModulo')
            ->join('cursos AS c', 'm.idCurso', '=', 'c.idCurso')
            ->join('grados AS g', 'c.idGrado', '=', 'g.idGrado')
            ->join('asignacion_aula_docente AS ad', 'g.idGrado', '=', 'ad.idAula')
            ->select('c.nombreCurso', 'g.seccion', DB::raw('COUNT(t.idTarea) AS tareas_pendientes'))
            ->where('t.revisado', 'no')
            ->where('ad.idDocente', $idDocente)
            ->groupBy('c.idCurso', 'g.seccion', 'c.nombreCurso')
            ->get();

        return response()->json(['success' => true, 'tareasPendientes' => $tareasPendientes]);
    }


    public function obtenerTareasPendientesPorCurso($idDocente)
    {
        $tareasPendientes = DB::table('tareas_alumnos as t')
            ->join('actividades as a', 't.idActividad', '=', 'a.idActividad')
            ->join('modulos as m', 'a.idModulo', '=', 'm.idModulo')
            ->join('cursos as c', 'm.idCurso', '=', 'c.idCurso')
            ->join('grados as g', 'c.idGrado', '=', 'g.idGrado')
            ->join('asignacion_aula_docente as ad', 'g.idGrado', '=', 'ad.idAula')
            ->select('c.nombreCurso', 'g.seccion', DB::raw('COUNT(t.idTarea) as tareas_pendientes'))
            ->where('t.revisado', 'no')
            ->where('ad.idDocente', $idDocente)
            ->groupBy('c.idCurso', 'g.seccion', 'c.nombreCurso')
            ->get();

        return response()->json(['success' => true, 'data' => $tareasPendientes]);
    }

    public function obtenerTareasPendientesPorModulo($idModulo)
    {
        $tareasPendientes = TareaAlumno::with(['usuario', 'actividad'])
            ->whereHas('actividad', function ($query) use ($idModulo) {
                $query->where('idModulo', $idModulo);
            })
            ->where('revisado', 'no')
            ->get();

        return response()->json(['success' => true, 'tareasPendientes' => $tareasPendientes]);
    }


    
    // Función para obtener materiales asignados con `idMaterial`
    public function obtenerMaterialesAsignadas($idModulo)
    {
        $materiales = Archivo::where('idModulo', $idModulo)->get(['id as idMaterial', 'nombre', 'tipo', 'ruta']);
        
        return response()->json([
            'success' => true,
            'data' => $materiales
        ]);
    }
    // Función para obtener actividades asignadas con `idActividad`
    public function obtenerActividadesAsignadas($idModulo)
    {
        // Obtener las actividades relacionadas con el módulo usando el `idModulo`
        $actividades = Actividad::where('idModulo', $idModulo)->get(['idActividad', 'titulo', 'descripcion', 'fecha_vencimiento']); // Incluye `idActividad` y otros campos necesarios
        
        return response()->json([
            'success' => true,
            'data' => $actividades
        ]);
    }

    public function eliminarArchivo($idMaterial)
    {
        try {
            // Buscar el material en la base de datos
            $material = Archivo::findOrFail($idMaterial);

            // Eliminar el archivo físico si existe
            $rutaArchivo = 'material/' . $material->ruta;
            if (Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            // Eliminar el registro del material en la base de datos
            $material->delete();

            return response()->json([
                'success' => true,
                'message' => 'Material eliminado correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el material',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function eliminarActividad($idActividad)
    {
        try {
            // Buscar la actividad en la base de datos
            $actividad = Actividad::findOrFail($idActividad);

            // Eliminar la actividad de la base de datos
            $actividad->delete();

            return response()->json([
                'success' => true,
                'message' => 'Actividad eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function actualizarActividad(Request $request, $idActividad)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'fecha_vencimiento' => 'required|date',
        ]);
    
        try {
            $actividad = Actividad::findOrFail($idActividad);
    
            Log::info("Datos enviados para actualizar:", $request->all());
    
            $actividad->titulo = $request->titulo;
            $actividad->descripcion = $request->descripcion;
            $actividad->fecha_vencimiento = $request->fecha_vencimiento;
            $actividad->save();
    
            return response()->json([
                'success' => true,
                'message' => 'Actividad actualizada correctamente',
                'data' => $actividad
            ]);
        } catch (\Exception $e) {
            Log::error("Error al actualizar la actividad: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la actividad',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerEstudiantes($idCurso)
    {
        // Consulta a la base de datos para obtener los estudiantes matriculados
        $estudiantes = DB::table('alumnosmatriculados as am')
            ->join('usuarios as u', 'am.idUsuario', '=', 'u.idUsuario')
            ->join('cursos as c', 'am.idGrado', '=', 'c.idGrado')
            ->select(
                'u.idUsuario',  // Incluye idUsuario en la selección
                DB::raw("CONCAT(u.nombres, ' ', u.apellidos) AS nombreCompleto"),
                'u.departamento',
                DB::raw("IFNULL(u.perfil, '/img/default-profile.jpg') AS perfilRuta")
            )
            ->where('c.idCurso', $idCurso)
            ->get();
    
        // Retornar los datos en formato JSON
        return response()->json([
            'success' => true,
            'data' => $estudiantes
        ]);
    }
    public function obtenerFotoPerfil($idUsuario)
    {
        // Buscar el usuario por id
        $usuario = Usuario::find($idUsuario);
    
        // Verificar si el usuario existe
        if (!$usuario) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }
    
        // Obtener la URL completa de la foto de perfil o null si no tiene foto
        $profileUrl = $usuario->perfil ? url("storage/{$usuario->perfil}") : null;
    
        // Retornar la respuesta en JSON con solo la URL de la foto de perfil o null
        return response()->json([
            'success' => true,
            'data' => [
                'perfil' => $profileUrl
            ]
        ]);
    }
}
