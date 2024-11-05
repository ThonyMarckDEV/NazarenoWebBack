<?php

namespace App\Http\Controllers;

use App\Models\Actividad;
use App\Models\AlumnoMatriculado;
use App\Models\Usuario;
use Illuminate\Http\Request;
use App\Models\AnuncioDocente;
use App\Models\TareaAlumno;
use App\Models\Curso;
use App\Models\Archivo;
use App\Models\AnuncioVisto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EstudianteController extends Controller
{
    // En EstudianteController.php
    public function perfilAlumno()
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

    public function uploadProfileImageAlumno(Request $request, $idUsuario)
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

    public function updateAlumno(Request $request, $idUsuario)
    {
        $docente = Usuario::find($idUsuario);
        if (!$docente || $docente->rol !== 'estudiante') {
            return response()->json(['success' => false, 'message' => 'Estudiante no encontrado'], 404);
        }

        $docente->update($request->only([
            'nombres', 'apellidos', 'dni', 'correo', 'edad', 'nacimiento',
            'sexo', 'direccion', 'telefono', 'departamento'
        ]));

        return response()->json(['success' => true, 'message' => 'Datos actualizados correctamente']);
    }


    public function listarCursosPorAlumno($idUsuario)
    {
        // Intentar obtener el grado del estudiante según la matrícula
        $gradoId = AlumnoMatriculado::where('idUsuario', $idUsuario)->value('idGrado');
    
        if (!$gradoId) {
            return response()->json(['success' => false, 'message' => 'El estudiante no tiene matrículas registradas.']);
        }
    
        // Obtener cursos en el grado del estudiante, asegurando el uso de `cursos.idGrado`
        $cursos = Curso::where('cursos.idGrado', $gradoId)
            ->join('grados', 'cursos.idGrado', '=', 'grados.idGrado')
            ->select('cursos.idCurso', 'cursos.nombreCurso', 'grados.nombreGrado', 'grados.seccion')
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
    
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function obtenerAnunciosPorCurso($nombreCurso, $seccion, Request $request)
    {
        // Obtener el id del alumno desde el request
        $idAlumno = $request->query('idAlumno');  // Asegúrate de obtenerlo como parámetro en la URL
        
        // Validar que idAlumno esté presente
        if (!$idAlumno) {
            return response()->json(['success' => false, 'message' => 'ID del alumno no proporcionado.'], 400);
        }
    
        // Obtener los IDs de los anuncios que ya han sido vistos por el alumno
        $anunciosVistos = AnuncioVisto::where('idAlumno', $idAlumno)->pluck('idAnuncio');
    
        // Obtener los anuncios relacionados con el curso y la sección, excluyendo los ya vistos por el alumno
        $anuncios = AnuncioDocente::with('docente')
            ->where('nombreCurso', $nombreCurso)
            ->where('seccion', $seccion)
            ->whereNotIn('idAnuncio', $anunciosVistos) // Filtrar anuncios no vistos
            ->get();
    
        // Formatear el resultado en un arreglo organizado
        $result = $anuncios->map(function ($anuncio) {
            return [
                'idAnuncio' => $anuncio->idAnuncio,
                'descripcion' => $anuncio->descripcion,
                'fecha' => $anuncio->fecha,
                'hora' => $anuncio->hora,
                'docente' => $anuncio->docente->nombres . ' ' . $anuncio->docente->apellidos, // Nombre completo del docente
            ];
        });
    
        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }


    public function marcarAnuncioRevisado(Request $request)
    {
        $request->validate([
            'idAnuncio' => 'required|exists:anuncios_docente,idAnuncio',
            'idAlumno' => 'required|exists:usuarios,idUsuario',
        ]);

        // Crear o actualizar el estado de visto
        $anuncioVisto = AnuncioVisto::firstOrCreate([
            'idAnuncio' => $request->idAnuncio,
            'idAlumno' => $request->idAlumno,
        ]);

        return response()->json(['success' => true, 'message' => 'Anuncio marcado como revisado']);
    }

    public function contarAnunciosNoVistos($idAlumno)
    {
        // Validar que el id del alumno esté presente
        if (!$idAlumno) {
            return response()->json(['success' => false, 'message' => 'ID del alumno no proporcionado.'], 400);
        }
    
        // Obtener el conteo de anuncios no vistos con una consulta cruda
        $anunciosNoVistos = DB::table('anuncios_docente AS ad')
            ->join('alumnosmatriculados AS am', 'am.idUsuario', '=', DB::raw($idAlumno))
            ->join('cursos AS c', function($join) {
                $join->on('c.idGrado', '=', 'am.idGrado')
                    ->on('c.nombreCurso', '=', 'ad.nombreCurso');
            })
            ->join('grados AS g', function($join) {
                $join->on('g.idGrado', '=', 'c.idGrado')
                    ->on('g.seccion', '=', 'ad.seccion');
            })
            ->leftJoin('anuncios_vistos AS av', function($join) use ($idAlumno) {
                $join->on('av.idAnuncio', '=', 'ad.idAnuncio')
                    ->where('av.idAlumno', '=', $idAlumno);
            })
            ->whereNull('av.id')
            ->count('ad.idAnuncio');
    
        return response()->json([
            'success' => true,
            'cantidad' => $anunciosNoVistos,
        ]);
    }

    public function contarAnunciosNoVistosPorCurso($idAlumno)
    {
        if (!$idAlumno) {
            return response()->json(['success' => false, 'message' => 'ID del alumno no proporcionado.'], 400);
        }

        // Obtener el conteo de anuncios no vistos por curso y sección
        $anunciosNoVistosPorCurso = DB::table('anuncios_docente AS ad')
            ->join('alumnosmatriculados AS am', 'am.idUsuario', '=', DB::raw($idAlumno))
            ->join('cursos AS c', function($join) {
                $join->on('c.idGrado', '=', 'am.idGrado')
                    ->on('c.nombreCurso', '=', 'ad.nombreCurso');
            })
            ->join('grados AS g', function($join) {
                $join->on('g.idGrado', '=', 'c.idGrado')
                    ->on('g.seccion', '=', 'ad.seccion');
            })
            ->leftJoin('anuncios_vistos AS av', function($join) use ($idAlumno) {
                $join->on('av.idAnuncio', '=', 'ad.idAnuncio')
                    ->where('av.idAlumno', '=', $idAlumno);
            })
            ->whereNull('av.id')
            ->select('ad.nombreCurso', 'ad.seccion', DB::raw('COUNT(ad.idAnuncio) as cantidad'))
            ->groupBy('ad.nombreCurso', 'ad.seccion')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $anunciosNoVistosPorCurso,
        ]);
    }


     // Función para obtener materiales de un módulo
     public function obtenerMateriales($idModulo)
     {
         $materiales = Archivo::where('idModulo', $idModulo)->get();
         
         return response()->json([
             'success' => true,
             'data' => $materiales
         ]);
     }
 
     // Función para obtener actividades de un módulo
     public function obtenerActividades($idModulo)
     {
         $actividades = Actividad::where('idModulo', $idModulo)->get();
         
         return response()->json([
             'success' => true,
             'data' => $actividades
         ]);
     }

     public function descargarArchivo($curso, $modulo, $archivo)
     {
         // Decodificar el nombre del archivo para manejar espacios y caracteres especiales
         $archivo = urldecode($archivo);
     
         // Construye la ruta completa del archivo en el almacenamiento público
         $filePath = storage_path("app/public/material/$curso/$modulo/$archivo");
     
         // Verifica si el archivo existe en el sistema de archivos
         if (file_exists($filePath)) {
             // Devuelve el archivo utilizando response()->download()
             return response()->download($filePath, $archivo, [
                 'Content-Type' => mime_content_type($filePath), // Define el tipo MIME del archivo
                 'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"'
             ]);
         } else {
             // Retorna un mensaje de error si el archivo no se encuentra
             return response()->json(['error' => 'Archivo no encontrado'], 404);
         }
     }

     public function subirTarea(Request $request)
    {
        // Validar los datos de la solicitud
        $request->validate([
            'idUsuario' => 'required|exists:usuarios,idUsuario',
            'idActividad' => 'required|exists:actividades,idActividad',
            'archivo' => 'required|file|max:10240' // Max 10MB
        ]);

        try {
            // Datos del usuario y actividad
            $idUsuario = $request->input('idUsuario');
            $idActividad = $request->input('idActividad');
            $nombreArchivo = $request->file('archivo')->getClientOriginalName();
            $tipoArchivo = $request->file('archivo')->getClientMimeType();
            $rutaArchivoCompleta = $request->file('archivo')->storeAs("tareas/{$idUsuario}/actividad_{$idActividad}", $nombreArchivo, 'public');
        
            // Log para verificar los datos
            Log::info('Datos de tarea a guardar:', [
                'idUsuario' => $idUsuario,
                'idActividad' => $idActividad,
                'archivo_nombre' => $nombreArchivo,
                'archivo_tipo' => $tipoArchivo,
                'ruta' => $rutaArchivoCompleta
            ]);
        
            // Registrar la tarea en la base de datos
            $tarea = TareaAlumno::create([
                'idUsuario' => $idUsuario,
                'idActividad' => $idActividad,
                'archivo_nombre' => $nombreArchivo,
                'archivo_tipo' => $tipoArchivo,
                'ruta' => $rutaArchivoCompleta,
                'revisado' => 'no'
            ]);
        
            return response()->json([
                'success' => true,
                'message' => 'Tarea subida correctamente',
                'data' => $tarea
            ], 201);
        } catch (\Exception $e) {
            // Registra el error para diagnosticar el problema
            Log::error('Error al guardar tarea en la base de datos:', ['exception' => $e]);
        
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la tarea',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function verificarEstadoActividad(Request $request)
    {
        $request->validate([
            'idUsuario' => 'required|exists:usuarios,idUsuario',
            'idActividad' => 'required|exists:actividades,idActividad',
        ]);

        $idUsuario = $request->input('idUsuario');
        $idActividad = $request->input('idActividad');

        $actividad = Actividad::find($idActividad);
        if (!$actividad) {
            return response()->json(['success' => false, 'message' => 'Actividad no encontrada'], 404);
        }

        $fechaActual = Carbon::now();
        $fechaVencimiento = Carbon::parse($actividad->fecha_vencimiento);

        // Verificar si la fecha de vencimiento ha pasado
        $fechaExpirada = $fechaVencimiento->isPast();

        // Verificar si ya existe una tarea subida por el usuario para esta actividad
        $tareaSubida = TareaAlumno::where('idUsuario', $idUsuario)
            ->where('idActividad', $idActividad)
            ->exists();

        return response()->json([
            'success' => true,
            'fechaExpirada' => $fechaExpirada,
            'tareaSubida' => $tareaSubida,
        ]);
    }

    public function obtenerCalificacionesPorModulo($idModulo, $idUsuario)
    {
        $actividadesConCalificaciones = DB::table('actividades')
            ->join('tareas_alumnos', 'actividades.idActividad', '=', 'tareas_alumnos.idActividad')
            ->select(
                'tareas_alumnos.idTarea', // Añadimos idTarea aquí
                'actividades.idActividad', 
                'actividades.titulo', 
                'tareas_alumnos.nota', 
                'tareas_alumnos.visto' // Añadimos el campo 'visto'
            )
            ->where('actividades.idModulo', $idModulo)
            ->where('tareas_alumnos.idUsuario', $idUsuario) // Filtrar por idUsuario
            ->where('tareas_alumnos.revisado', 'si') // Filtrar solo tareas revisadas
            ->get();

        $result = $actividadesConCalificaciones->map(function ($actividad) {
            $color = 'text-green-500'; // Color verde por defecto

            if ($actividad->nota < 11) {
                $color = 'text-red-500';
            } elseif ($actividad->nota < 15) {
                $color = 'text-orange-500';
            }

            return [
                'idTarea' => $actividad->idTarea,
                'idActividad' => $actividad->idActividad,
                'titulo' => $actividad->titulo,
                'nota' => $actividad->nota,
                'visto' => $actividad->visto,
                'color' => $color,
            ];
        });

        return response()->json(['success' => true, 'data' => $result]);
    }


    public function obtenerTareasRevisadasPorUsuario($idUsuario)
    {
        $totalTareasRevisadas = DB::table('tareas_alumnos')
            ->where('revisado', 'si')
            ->where('visto', 'no') // Filtrar solo tareas no vistas
            ->where('idUsuario', $idUsuario)
            ->count();

        return response()->json(['success' => true, 'totalTareasRevisadas' => $totalTareasRevisadas]);
    }


    public function obtenerTareasRevisadasPorCurso($idUsuario)
    {
        // Validar que el id del usuario esté presente
        if (!$idUsuario) {
            return response()->json(['success' => false, 'message' => 'ID del usuario no proporcionado.'], 400);
        }

        $tareasRevisadas = DB::table('tareas_alumnos as t')
            ->join('actividades as a', 't.idActividad', '=', 'a.idActividad')
            ->join('modulos as m', 'a.idModulo', '=', 'm.idModulo')
            ->join('cursos as c', 'm.idCurso', '=', 'c.idCurso')
            ->join('grados as g', 'c.idGrado', '=', 'g.idGrado')
            ->select('c.nombreCurso', 'g.seccion', DB::raw('COUNT(t.idTarea) as tareas_revisadas'))
            ->where('t.revisado', 'si')
            ->where('t.visto', 'no') // Filtra por las tareas no vistas
            ->where('t.idUsuario', $idUsuario)
            ->groupBy('c.idCurso', 'g.seccion', 'c.nombreCurso')
            ->get();

        return response()->json(['success' => true, 'data' => $tareasRevisadas]);
    }
        

    public function obtenerTareasRevisadasPorModulo($idUsuario, $idModulo)
    {
        $tareasRevisadas = DB::table('tareas_alumnos as t')
            ->join('actividades as a', 't.idActividad', '=', 'a.idActividad')
            ->where('t.idUsuario', $idUsuario)
            ->where('a.idModulo', $idModulo)
            ->where('t.revisado', 'si')
            ->where('t.visto', 'no') // Filtrar solo las tareas que aún no han sido vistas
            ->count();

        return response()->json(['success' => true, 'tareasRevisadas' => $tareasRevisadas]);
    }

    public function marcarComoVisto($idTarea, $idUsuario)
    {
        // Busca la tarea en base al idUsuario e idActividad
        $tarea = TareaAlumno::where('idTarea', $idTarea)
                            ->where('idUsuario', $idUsuario)
                            ->first();

        if (!$tarea) {
            return response()->json(['success' => false, 'message' => 'Tarea no encontrada'], 404);
        }

        // Marcar la tarea como vista
        $tarea->visto = 'si';
        $tarea->save();

        return response()->json(['success' => true, 'message' => 'Tarea marcada como vista']);
    }
}
