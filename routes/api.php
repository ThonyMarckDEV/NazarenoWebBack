<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\EstudianteController;
use App\Http\Controllers\DocenteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

//RUTAS

//================================================================================================
// RUTAS LIBRES

    // RUTA PARA LISTAR Especialidad
    Route::get('listarEspecialidades', [AdminController::class, 'listarEspecialidades']);

    // RUTA PARA LISTAR Grados
    Route::get('listarGrados', [AdminController::class, 'listarGrados']);

     // RUTA PARA LISTAR usuarios
    Route::get('listarUsuarios', [AdminController::class, 'listarUsuarios']);



    Route::get('listarEstudiantes', [AdminController::class, 'listarEstudiantes']);
    Route::get('listarGradosCupos', [AdminController::class, 'listarGradosCupos']);


    Route::get('listarMatriculas', [AdminController::class, 'listarMatriculas']);


    Route::get('listarDocentes', [AdminController::class, 'listarDocentes']);
  

    // Ruta para listar asignaciones de docentes a especialidades
    Route::get('listarAsignacionesDocente', [AdminController::class, 'listarAsignaciones']);

    Route::get('listarCursos', [AdminController::class, 'listarCursos']);

    // Ruta para asignaciones de docentes a aulas
    Route::get('asignaciones', [AdminController::class, 'listarTodasLasAsignaciones']);
    
    Route::get('docente/{idDocente}/cursos', [DocenteController::class, 'listarCursosPorDocente']);

    Route::get('curso/{idCurso}/modulos', [DocenteController::class, 'listarModulosPorCurso']);


    Route::get('/cursos/{idCurso}/estudiantes', [DocenteController::class, 'obtenerEstudiantes']);
    Route::get('/alumnos/{idUsuario}/foto-perfil', [DocenteController::class, 'obtenerFotoPerfil']);
 
   
//================================================================================================
        //RUTAS PROTEGIDAS AUTH

        //RUTA PARA QUE LOS USUAIOS SE LOGEEN POR EL CONTROLLADOR AUTHCONTROLLER
        Route::post('login', [AuthController::class, 'login']);

        Route::post('logout', [AuthController::class, 'logout']);

        
        Route::post('refresh-token', [AuthController::class, 'refreshToken']);

        Route::post('update-activity', [AuthController::class, 'updateLastActivity']);

//================================================================================================


//================================================================================================
        //RUTAS PROTEGIDAS A
// RUTAS PARA ADMINISTRADOR VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:admin'])->group(function () {

    // RUTA PARA QUE SOLO LOS USUARIOS CON ROL ADMIN REGISTREN USUARIOS
    Route::post('register', [AdminController::class, 'register']);

    // RUTA PARA QUE SOLO LOS USUARIOS CON ROL ADMIN REGISTREN ESPECIALIDAD
    Route::post('agregarEspecialidad', [AdminController::class, 'agregarEspecialidad']);

    // RUTA PARA QUE SOLO LOS USUARIOS CON ROL ADMIN REGISTREN CURSOS
    Route::post('agregarCurso', [AdminController::class, 'agregarCurso']);

    //RUTAS GESTION DE USUARIOS
    Route::delete('eliminarUsuario/{id}', [AdminController::class, 'eliminarUsuario']);
    Route::put('actualizarUsuario/{id}', [AdminController::class, 'actualizarUsuario']);

    //RUTAS MATRICULA
    Route::post('matricularEstudiante', [AdminController::class, 'matricularEstudiante']);
    Route::delete('eliminarMatricula/{idMatricula}', [AdminController::class, 'eliminarMatricula']);    

    //RUTAS ESPECIALIDAD
    Route::post('asignarEspecialidadDocente', [AdminController::class, 'asignarEspecialidadDocente']);
    Route::delete('eliminarAsignacionDocente/{id}', [AdminController::class, 'eliminarAsignacion']);
    Route::delete('eliminarEspecialidad/{idEspecialidad}', [AdminController::class, 'eliminarEspecialidad']);

    //RUTAS CURSOS
    Route::delete('eliminarCurso/{idCurso}', [AdminController::class, 'eliminarCurso']);

    //RUTAS ASIGANCION AULA
    Route::post('docente/asignar-aula', [AdminController::class, 'asignarAulaDocente']);

    Route::delete('docente/asignacion/{idAsignacion}', [AdminController::class, 'eliminarAsignacionAulaDocente']);

});




// RUTAS PARA DOCENTE VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:docente'])->group(function () {
    
    Route::get('perfilDocente', [DocenteController::class, 'perfilDocente']);
    Route::post('uploadProfileImageDocente/{idUsuario}', [DocenteController::class, 'uploadProfileImageDocente']);
    Route::put('updateDocente/{idUsuario}', [DocenteController::class, 'updateDocente']);

    Route::post('anuncios', [DocenteController::class, 'store']);
    
    Route::post('materiales', [DocenteController::class, 'agregarArchivo']);
   

    Route::post('actividades', [DocenteController::class, 'agregarActividad']);

    Route::get('/modulo/{idModulo}/tareas', [DocenteController::class, 'obtenerTareas']);

    Route::post('tarea/revisar', [DocenteController::class, 'revisarTarea']);

    Route::get('/docente/{idDocente}/tareas/pendientes', [DocenteController::class,'obtenerTareasPendientesPorDocente']);
    
    Route::get('/docente/{idDocente}/tareas-pendientes', [DocenteController::class, 'obtenerTareasPendientesPorCurso']);

    // Ruta para obtener tareas pendientes por módulo
    Route::get('/modulo/{idModulo}/tareas-pendientes', [DocenteController::class, 'obtenerTareasPendientesPorModulo']);

    Route::get('/modulo/{idModulo}/materialesAsignadas', [DocenteController::class, 'obtenerMaterialesAsignadas']);
    Route::get('/modulo/{idModulo}/actividadesAsignadas', [DocenteController::class, 'obtenerActividadesAsignadas']);
    Route::delete('/material/{idMaterial}', [DocenteController::class, 'eliminarArchivo']);
    Route::delete('/actividad/{idActividad}', [DocenteController::class, 'eliminarActividad']);
    Route::put('/actualizaractividad/{idActividad}', [DocenteController::class, 'actualizarActividad']);
});



// RUTAS PARA ESTUDIANTE VALIDADA POR MIDDLEWARE AUTH (PARA TOKEN JWT) Y CHECKROLE (PARA VALIDAR ROL DEL TOKEN)
Route::middleware(['auth.jwt', 'checkRoleMW:estudiante'])->group(function () {

    Route::get('perfilAlumno', [EstudianteController::class, 'perfilAlumno']);
    Route::post('uploadProfileImageAlumno/{idUsuario}', [EstudianteController::class, 'uploadProfileImageAlumno']);
    Route::put('updateAlumno/{idUsuario}', [EstudianteController::class, 'updateAlumno']);

    //RUTAS ALUMNO
    Route::get('estudiante/{idUsuario}/cursos', [EstudianteController::class, 'listarCursosPorAlumno']);


    Route::get('/cursos/{nombreCurso}/seccion/{seccion}/anuncios', [EstudianteController::class, 'obtenerAnunciosPorCurso']);
    Route::post('/anuncios/{idAnuncio}/revisar', [EstudianteController::class, 'marcarAnuncioRevisado']);
    Route::get('/alumno/{idAlumno}/anuncios/no-vistos', [EstudianteController::class, 'contarAnunciosNoVistos']);
    Route::get('/alumno/{idAlumno}/anuncios/no-vistos/por-curso', [EstudianteController::class, 'contarAnunciosNoVistosPorCurso']);
    Route::get('/modulo/{idModulo}/materiales', [EstudianteController::class, 'obtenerMateriales']);
    Route::get('/modulo/{idModulo}/actividades', [EstudianteController::class, 'obtenerActividades']);
    Route::get('/descargar/{curso}/{modulo}/{archivo}', [EstudianteController::class, 'descargarArchivo']);
    Route::post('/subir-tarea', [EstudianteController::class, 'subirTarea']);
    Route::post('/verificar-estado-actividad', [EstudianteController::class, 'verificarEstadoActividad']);
    Route::get('/modulos/{idModulo}/calificaciones/{idUsuario}', [EstudianteController::class, 'obtenerCalificacionesPorModulo']);
    Route::get('tareas-revisadas/{idUsuario}', [EstudianteController::class,'obtenerTareasRevisadasPorUsuario']);
    Route::get('/tareas-revisadas-por-curso/{idUsuario}', [EstudianteController::class,'obtenerTareasRevisadasPorCurso']);
    Route::get('tareas-revisadas-por-modulo/{idUsuario}/{idModulo}', action: [EstudianteController::class, 'obtenerTareasRevisadasPorModulo']);
    Route::put('/tareas/{idTarea}/{idUsuario}/marcar-visto', [EstudianteController::class, 'marcarComoVisto']);
});


//================================================================================================

