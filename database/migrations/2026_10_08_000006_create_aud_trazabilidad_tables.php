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
        // 1. Trazabilidad Transaccional CDC (Change Data Capture)
        Schema::create('aud_trazabilidad', function (Blueprint $table) {
            $table->id('aud_id')->comment('Identificador unico del registro de auditoria');
            $table->uuid('aud_urbanizacion_id')->nullable()->comment('Urbanizacion donde ocurrio el evento');
            $table->foreignId('aud_user_id')->nullable()->constrained('sec_users', 'usr_id')->nullOnDelete();
            $table->string('aud_user_nombre', 150)->nullable()->comment('Nombre del usuario para persistencia historica');
            $table->string('aud_evento', 30)->comment('INSERT, UPDATE, DELETE, RESTORE, LOGIN, LOGOUT, EXPORT_PDF, EXPORT_EXCEL');
            $table->string('aud_tabla', 80)->comment('Nombre de la tabla afectada (ej. op_control_accesos, core_personas)');
            $table->string('aud_registro_id', 50)->comment('PK del registro afectado');
            $table->jsonb('aud_old_values')->nullable()->comment('Estado previo a la modificacion');
            $table->jsonb('aud_new_values')->nullable()->comment('Estado posterior a la modificacion');
            $table->jsonb('aud_diff')->nullable()->comment('Diferencia calculada exacta');
            $table->string('aud_ip_address', 45)->nullable();
            $table->text('aud_user_agent')->nullable();
            $table->timestampTz('aud_created_at')->useCurrent()->comment('Momento exacto del log');

            $table->index('aud_urbanizacion_id', 'idx_aud_trazabilidad_urb');
            $table->index('aud_user_id', 'idx_aud_trazabilidad_usr');
            $table->index(['aud_tabla', 'aud_registro_id'], 'idx_aud_trazabilidad_tabla_registro');
            $table->index('aud_created_at', 'idx_aud_trazabilidad_fecha');
        });

        // 2. Registro Específico de Descargas e Impresiones de Reportes
        Schema::create('aud_reportes_descargas', function (Blueprint $table) {
            $table->id('rep_id');
            $table->uuid('rep_urbanizacion_id')->nullable();
            $table->foreignId('rep_user_id')->nullable()->constrained('sec_users', 'usr_id')->nullOnDelete();
            $table->string('rep_modulo', 50)->comment('Modulo del reporte (accesos, finanzas, inmuebles)');
            $table->string('rep_formato', 15)->comment('PDF, EXCEL, CSV');
            $table->string('rep_nombre_archivo', 255)->comment('Nombre generado del reporte');
            $table->jsonb('rep_filtros_aplicados')->nullable()->comment('Rango de fechas, garitas, estados filtrados');
            $table->integer('rep_total_filas')->default(0)->comment('Cantidad de registros exportados');
            $table->string('rep_ip_address', 45)->nullable();
            $table->timestampTz('rep_created_at')->useCurrent();

            $table->index('rep_urbanizacion_id', 'idx_aud_reportes_urb');
            $table->index('rep_user_id', 'idx_aud_reportes_usr');
            $table->index('rep_created_at', 'idx_aud_reportes_fecha');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aud_reportes_descargas');
        Schema::dropIfExists('aud_trazabilidad');
    }
};
