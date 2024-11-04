<?php

// database/migrations/xxxx_xx_xx_create_anuncios_docente_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaAnunciosDocente extends Migration
{
    public function up()
    {
        Schema::create('anuncios_docente', function (Blueprint $table) {
            $table->id('idAnuncio');
            $table->string('nombreCurso', 100);
            $table->char('seccion', 1);
            $table->text('descripcion');
            $table->date('fecha');
            $table->time('hora');
            $table->unsignedBigInteger('idDocente');

            // Foreign key constraint
            $table->foreign('idDocente')->references('idUsuario')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('anuncios_docente');
    }
}
