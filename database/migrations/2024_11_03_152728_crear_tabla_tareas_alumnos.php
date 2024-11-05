<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaTareasAlumnos extends Migration
{
    public function up()
    {
        Schema::create('tareas_alumnos', function (Blueprint $table) {
            $table->id('idTarea');
            $table->unsignedBigInteger('idUsuario')->nullable();
            $table->unsignedBigInteger('idActividad')->nullable();
            $table->decimal('nota', 3, 1)->nullable();
            $table->string('archivo_nombre', 255)->nullable();
            $table->string('archivo_tipo', 100)->nullable();
            $table->string('ruta', 255)->nullable(); // Cambiado de contenido a ruta
            $table->timestamp('fecha_subida')->useCurrent();
            $table->string('revisado', 2)->default('no');
            $table->string('visto', 2)->default('no'); // Nuevo campo agregado

            $table->foreign('idUsuario')->references('idUsuario')->on('usuarios')->onDelete('set null');
            $table->foreign('idActividad')->references('idActividad')->on('actividades')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tareas_alumnos');
    }
}
