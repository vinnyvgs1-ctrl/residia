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
        // 1. Tabla de Paquetería y Correspondencia
        Schema::create('op_paqueteria', function (Blueprint $table) {
            $table->uuid('paq_id')->primary();
            $table->uuid('paq_urbanizacion_id');
            $table->unsignedBigInteger('paq_inmueble_id');
            $table->unsignedBigInteger('paq_guardia_recibe_usr_id');
            $table->unsignedBigInteger('paq_guardia_entrega_usr_id')->nullable();
            $table->string('paq_empresa_courier', 100); // Servientrega, DHL, Amazon, etc.
            $table->string('paq_codigo_guia', 100)->nullable();
            $table->string('paq_destinatario_nombre', 150)->nullable();
            $table->string('paq_descripcion', 255)->nullable(); // Paquete mediano, sobre, etc.
            $table->string('paq_estado', 20)->default('EN_GARITA'); // EN_GARITA, ENTREGADO, DEVUELTO
            $table->timestamp('paq_fecha_recepcion')->default(now());
            $table->timestamp('paq_fecha_entrega')->nullable();
            $table->string('paq_retirado_por', 150)->nullable(); // Nombre de quien retiró
            $table->text('paq_observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('paq_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreign('paq_inmueble_id')->references('inm_id')->on('core_inmuebles')->onDelete('cascade');
            $table->foreign('paq_guardia_recibe_usr_id')->references('usr_id')->on('sec_users')->onDelete('cascade');
            $table->index(['paq_urbanizacion_id', 'paq_estado']);
        });

        // 2. Tabla de Cuaderno Digital de Novedades & Bitácora de Turno
        Schema::create('op_novedades_turno', function (Blueprint $table) {
            $table->uuid('nov_id')->primary();
            $table->uuid('nov_urbanizacion_id');
            $table->unsignedBigInteger('nov_guardia_usr_id');
            $table->unsignedBigInteger('nov_garita_id')->nullable();
            $table->string('nov_tipo', 50)->default('RONDA'); // RONDA, INCIDENCIA, RELEVO, MANTENIMIENTO, ALERTA
            $table->string('nov_titulo', 150);
            $table->text('nov_detalle');
            $table->string('nov_prioridad', 20)->default('NORMAL'); // BAJA, NORMAL, ALTA, CRITICA
            $table->timestamp('nov_fecha_hora')->default(now());
            $table->boolean('nov_resuelta')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('nov_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreign('nov_guardia_usr_id')->references('usr_id')->on('sec_users')->onDelete('cascade');
            $table->foreign('nov_garita_id')->references('gar_id')->on('op_garitas')->onDelete('set null');
            $table->index(['nov_urbanizacion_id', 'nov_tipo', 'nov_prioridad']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_novedades_turno');
        Schema::dropIfExists('op_paqueteria');
    }
};
