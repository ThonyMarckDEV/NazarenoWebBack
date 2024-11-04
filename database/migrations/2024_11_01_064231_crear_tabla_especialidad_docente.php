<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaEspecialidadDocente extends Migration
{
    public function up()
    {
        Schema::create('especialidad_docente', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idEspecialidad');
            $table->unsignedBigInteger('idDocente');
            
            $table->foreign('idEspecialidad')->references('idEspecialidad')->on('especialidades')->onDelete('cascade');
            $table->foreign('idDocente')->references('idUsuario')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('especialidad_docente');
    }
}
