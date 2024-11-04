<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaActividades extends Migration
{
    public function up()
    {
        Schema::create('actividades', function (Blueprint $table) {
            $table->id('idActividad');
            $table->string('titulo');
            $table->string('descripcion');
            $table->date('fecha');
            $table->date('fecha_vencimiento');
            $table->unsignedBigInteger('idModulo');
            
            // Definir la clave foránea correctamente
            $table->foreign('idModulo')
                  ->references('idModulo')->on('modulos')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('actividades');
    }
}
