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
        // 1. Garitas de Control
        Schema::create('op_garitas', function (Blueprint $table) {
            $table->id('gar_id')->comment('Identificador de la garita');
            $table->uuid('gar_urbanizacion_id');
            $table->foreign('gar_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->string('gar_nombre', 100)->comment('Nombre descriptivo (ej. Garita Principal, Garita de Servicio)');
            $table->string('gar_ubicacion', 255)->nullable()->comment('Referencia o puerta de acceso');
            $table->boolean('gar_permite_vehicular')->default(true);
            $table->boolean('gar_permite_peatonal')->default(true);
            $table->boolean('gar_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Control de Accesos (Bitácora de Entradas, Permanencia y Salidas)
        Schema::create('op_control_accesos', function (Blueprint $table) {
            $table->id('acc_id')->comment('Identificador unico de la transaccion de acceso');
            $table->uuid('acc_urbanizacion_id');
            $table->foreign('acc_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreignId('acc_persona_id')->constrained('core_personas', 'per_id')->comment('Persona que ingresa');
            $table->foreignId('acc_inmueble_id')->nullable()->constrained('core_inmuebles', 'inm_id')->nullOnDelete()->comment('Destino autorizado');
            $table->string('acc_tipo_ingreso', 20)->default('VEHICULAR')->comment('PEATONAL, VEHICULAR');
            $table->string('acc_rol_visita', 30)->default('VISITA')->comment('VISITA, RESIDENTE, DELIVERY, PROVEEDOR, CONTRATISTA, TAXI');
            $table->string('acc_empresa', 100)->nullable()->comment('Empresa o entidad para delivery/proveedor');
            
            // Datos del Vehículo (si aplica)
            $table->string('acc_vehiculo_placa', 20)->nullable()->comment('Placa del vehiculo');
            $table->string('acc_vehiculo_marca', 50)->nullable();
            $table->string('acc_vehiculo_modelo', 50)->nullable();
            $table->string('acc_vehiculo_color', 30)->nullable();
            $table->smallInteger('acc_cantidad_ocupantes')->default(1)->comment('Total de personas dentro del auto');
            $table->jsonb('acc_ocupantes_detalle')->nullable()->comment('Lista estructurada con cedulas y nombres de acompanantes');

            // Garita y Guardia de Entrada
            $table->foreignId('acc_garita_ingreso_id')->nullable()->constrained('op_garitas', 'gar_id')->nullOnDelete();
            $table->foreignId('acc_guardia_ingreso_usr_id')->nullable()->constrained('sec_users', 'usr_id')->nullOnDelete();
            $table->timestampTz('acc_fecha_ingreso')->comment('Momento exacto del ingreso');

            // Garita y Guardia de Salida
            $table->foreignId('acc_garita_salida_id')->nullable()->constrained('op_garitas', 'gar_id')->nullOnDelete();
            $table->foreignId('acc_guardia_salida_usr_id')->nullable()->constrained('sec_users', 'usr_id')->nullOnDelete();
            $table->timestampTz('acc_fecha_salida')->nullable()->comment('Momento exacto de la salida');
            $table->integer('acc_duracion_minutos')->nullable()->comment('Calculado al momento de salida');

            // Estados y Control de Semáforo
            $table->string('acc_estado', 20)->default('DENTRO')->comment('DENTRO, SALIO, VENCIDO, ANULADO');
            $table->boolean('acc_tiempo_excedido')->default(false)->comment('Semáforo amarillo/rojo de permanencia');
            $table->text('acc_observaciones')->nullable();
            $table->timestamps();

            $table->index('acc_urbanizacion_id', 'idx_op_control_accesos_urb');
            $table->index('acc_persona_id', 'idx_op_control_accesos_per');
            $table->index('acc_vehiculo_placa', 'idx_op_control_accesos_placa');
            $table->index(['acc_urbanizacion_id', 'acc_estado'], 'idx_op_control_accesos_estado');
            $table->index('acc_fecha_ingreso', 'idx_op_control_accesos_ingreso');
        });

        // 3. Lista Negra / Veto de Seguridad Específico de Garita
        Schema::create('op_lista_negra', function (Blueprint $table) {
            $table->id('lis_id');
            $table->uuid('lis_urbanizacion_id');
            $table->foreign('lis_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreignId('lis_persona_id')->nullable()->constrained('core_personas', 'per_id')->onDelete('cascade');
            $table->string('lis_placa_vehiculo', 20)->nullable()->comment('Placa vetada aunque cambie de chofer');
            $table->text('lis_motivo')->comment('Razon legal, policial o administrativa del bloqueo');
            $table->foreignId('lis_registrado_por_usr_id')->nullable()->constrained('sec_users', 'usr_id')->nullOnDelete();
            $table->boolean('lis_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lis_urbanizacion_id', 'lis_placa_vehiculo'], 'idx_op_lista_negra_placa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('op_lista_negra');
        Schema::dropIfExists('op_control_accesos');
        Schema::dropIfExists('op_garitas');
    }
};
