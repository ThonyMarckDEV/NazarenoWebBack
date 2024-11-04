<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaModulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->bigIncrements('idModulo'); // Clave primaria
            $table->string('nombre', 255);
            $table->unsignedBigInteger('idCurso');
            $table->foreign('idCurso')->references('idCurso')->on('cursos')->onDelete('cascade'); // Relación con el curso
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('modulos');
    }
}
