<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sec_temas', function (Blueprint $table) {
            $table->id('tem_id')->comment('Identificador unico del tema');
            $table->string('tem_codigo', 50)->unique()->comment('Codigo identificador del tema (ej. robledal-dark, robledal-light)');
            $table->string('tem_nombre', 100)->comment('Nombre descriptivo del tema visual');
            $table->text('tem_descripcion')->nullable()->comment('Descripcion de uso o contexto del tema');
            $table->jsonb('tem_variables_css')->comment('Diccionario estructurado de variables CSS para inyeccion dinamica');
            $table->boolean('tem_es_oscuro')->default(false)->comment('Indica si la paleta corresponde a modo oscuro');
            $table->boolean('tem_es_sistema')->default(false)->comment('Indica si es un tema oficial del sistema no eliminable');
            $table->boolean('tem_activo')->default(true)->comment('Estado de disponibilidad del tema');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_temas');
    }
};
