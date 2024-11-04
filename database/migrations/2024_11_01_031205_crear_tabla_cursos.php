<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaCursos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cursos', function (Blueprint $table) {
            $table->id('idCurso'); // Clave primaria
            $table->string('nombreCurso', 255);
            $table->unsignedBigInteger('idEspecialidad');
            $table->unsignedBigInteger('idGrado');
            $table->foreign('idEspecialidad')->references('idEspecialidad')->on('especialidades')->onDelete('cascade');
            $table->foreign('idGrado')->references('idGrado')->on('grados')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cursos');
    }
}
