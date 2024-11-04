<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaAsignacionAulaDocente extends Migration
{ public function up()
    {
        Schema::create('asignacion_aula_docente', function (Blueprint $table) {
            $table->id('idAsignacion');
            $table->unsignedBigInteger('idDocente');
            $table->unsignedBigInteger('idAula');

            $table->foreign('idDocente')->references('idUsuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('idAula')->references('idGrado')->on('grados')->onDelete('cascade');
            
            // Evita duplicados para la combinación de idDocente y idAula
            $table->unique(['idDocente', 'idAula']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('asignacion_aula_docente');
    }
}
