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
        Schema::create('core_inmueble_vehiculos', function (Blueprint $table) {
            $table->id('veh_id');
            $table->unsignedBigInteger('veh_inmueble_id');
            $table->unsignedBigInteger('veh_persona_id')->nullable(); // Propietario habitual del vehículo
            $table->string('veh_placa', 15);
            $table->string('veh_marca', 60)->nullable();
            $table->string('veh_modelo', 60)->nullable();
            $table->string('veh_color', 40)->nullable();
            $table->string('veh_tipo', 30)->default('AUTOMOVIL'); // AUTOMOVIL, SUV, MOTOCICLETA, CAMIONETA
            $table->string('veh_tag_rfid', 64)->nullable()->unique();
            $table->string('veh_sticker_lpr', 64)->nullable();
            $table->boolean('veh_es_autorizado')->default(true);
            $table->text('veh_observaciones')->nullable();
            $table->boolean('veh_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('veh_inmueble_id')->references('inm_id')->on('core_inmuebles')->onDelete('cascade');
            $table->foreign('veh_persona_id')->references('per_id')->on('core_personas')->onDelete('set null');

            $table->index(['veh_inmueble_id', 'veh_placa'], 'idx_inmueble_placa');
            $table->index('veh_tag_rfid', 'idx_vehiculo_rfid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_inmueble_vehiculos');
    }
};