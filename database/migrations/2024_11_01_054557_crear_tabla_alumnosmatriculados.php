<?php
// database/migrations/YYYY_MM_DD_create_alumnosmatriculados_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CrearTablaAlumnosMatriculados extends Migration
{
    public function up()
    {
        Schema::create('alumnosmatriculados', function (Blueprint $table) {
            $table->id('idMatricula');
            $table->unsignedBigInteger('idUsuario');
            $table->unsignedBigInteger('idGrado');
            $table->timestamp('fechaMatricula')->useCurrent();

            $table->foreign('idUsuario')->references('idUsuario')->on('usuarios')->onDelete('cascade');
            $table->foreign('idGrado')->references('idGrado')->on('grados')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('alumnosmatriculados');
    }
}
