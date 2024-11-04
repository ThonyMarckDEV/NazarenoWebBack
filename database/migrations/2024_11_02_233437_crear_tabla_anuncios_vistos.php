<?php

// database/migrations/2024_11_02_000000_create_anuncios_vistos_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaAnunciosVistos extends Migration
{
    public function up()
    {
        Schema::create('anuncios_vistos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idAnuncio');
            $table->unsignedBigInteger('idAlumno');
    
            // Foreign keys
            $table->foreign('idAnuncio')
                ->references('idAnuncio')
                ->on('anuncios_docente')
                ->onDelete('cascade');
                
            $table->foreign('idAlumno')
                ->references('idUsuario') // Ajusta al nombre correcto en `usuarios`
                ->on('usuarios')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('anuncios_vistos');
    }
}
