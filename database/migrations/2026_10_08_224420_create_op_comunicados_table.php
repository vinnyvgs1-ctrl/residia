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
        Schema::create('op_comunicados', function (Blueprint $table) {
            $table->uuid('com_id')->primary();
            $table->uuid('com_urbanizacion_id');
            $table->unsignedBigInteger('com_autor_usr_id');
            $table->string('com_titulo', 200);
            $table->text('com_contenido');
            $table->string('com_categoria', 50)->default('GENERAL'); // GENERAL, MANTENIMIENTO, SEGURIDAD, ASAMBLEA, URGENTE
            $table->string('com_prioridad', 20)->default('NORMAL'); // BAJA, NORMAL, ALTA, URGENTE
            $table->date('com_fecha_publicacion')->default(now());
            $table->date('com_fecha_expiracion')->nullable();
            $table->boolean('com_fijado')->default(false); // Anclado arriba
            $table->boolean('com_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('com_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreign('com_autor_usr_id')->references('usr_id')->on('sec_users')->onDelete('cascade');
            $table->index(['com_urbanizacion_id', 'com_activo', 'com_prioridad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_comunicados');
    }
};
