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
        Schema::create('op_invitaciones_qr', function (Blueprint $table) {
            $table->uuid('inv_id')->primary();
            $table->uuid('inv_urbanizacion_id');
            $table->unsignedBigInteger('inv_inmueble_id')->nullable();
            $table->unsignedBigInteger('inv_usuario_creador_id');
            $table->string('inv_codigo_token', 64)->unique();
            $table->string('inv_visitante_identificacion', 30)->nullable();
            $table->string('inv_visitante_nombre', 150);
            $table->string('inv_visitante_placa', 15)->nullable();
            $table->string('inv_tipo_ingreso', 20)->default('VEHICULAR'); // VEHICULAR / PEATONAL
            $table->timestamp('inv_valido_desde');
            $table->timestamp('inv_valido_hasta');
            $table->integer('inv_usos_permitidos')->default(1);
            $table->integer('inv_usos_realizados')->default(0);
            $table->string('inv_estado', 20)->default('ACTIVO'); // ACTIVO, USADO, EXPIRADO, CANCELADO
            $table->text('inv_observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('inv_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreign('inv_inmueble_id')->references('inm_id')->on('core_inmuebles')->onDelete('set null');
            $table->foreign('inv_usuario_creador_id')->references('usr_id')->on('sec_users')->onDelete('cascade');

            $table->index(['inv_urbanizacion_id', 'inv_codigo_token'], 'idx_invitaciones_token');
            $table->index(['inv_estado', 'inv_valido_hasta'], 'idx_invitaciones_estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_invitaciones_qr');
    }
};