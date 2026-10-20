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
        // 1. Maestro de Urbanizaciones (SaaS Tenants)
        Schema::create('core_urbanizaciones', function (Blueprint $table) {
            $table->uuid('urb_id')->primary()->comment('Identificador UUID unico de la urbanizacion');
            $table->string('urb_codigo', 30)->unique()->comment('Codigo alfanumerico (ej. URB-ROBLEDAL-01)');
            $table->string('urb_nombre', 150)->comment('Nombre legal o comercial del conjunto / urbanizacion');
            $table->string('urb_ruc', 20)->nullable()->comment('RUC o identificacion tributaria');
            $table->string('urb_direccion', 255)->comment('Direccion geografica');
            $table->string('urb_ciudad', 100)->default('Quito')->comment('Ciudad donde se ubica la urbanizacion');
            $table->string('urb_telefono', 30)->nullable()->comment('Telefono de administracion');
            $table->foreignId('urb_tema_id')->nullable()->constrained('sec_temas', 'tem_id')->nullOnDelete()->comment('Tema visual institucional de la urbanizacion');
            $table->jsonb('urb_configuracion')->nullable()->comment('Reglas de garita, tiempo maximo de visitas, tolerancias');
            $table->boolean('urb_activo')->default(true)->comment('Estado de la suscripcion de la urbanizacion');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Asignación de Usuarios a Urbanizaciones (N a M con roles)
        Schema::create('sec_urbanizacion_usuarios', function (Blueprint $table) {
            $table->id('uxu_id');
            $table->uuid('uxu_urbanizacion_id');
            $table->foreign('uxu_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->foreignId('uxu_user_id')->constrained('sec_users', 'usr_id')->onDelete('cascade');
            $table->boolean('uxu_es_predeterminada')->default(false)->comment('Urbanizacion activa al iniciar sesion');
            $table->boolean('uxu_activo')->default(true);
            $table->timestamps();

            $table->unique(['uxu_urbanizacion_id', 'uxu_user_id'], 'uq_sec_urbanizacion_usuarios');
        });

        // 3. Roles asignados al Usuario dentro de esa Urbanización específica
        Schema::create('sec_usuario_roles', function (Blueprint $table) {
            $table->id('uxr_id');
            $table->foreignId('uxr_urbanizacion_usuario_id')->constrained('sec_urbanizacion_usuarios', 'uxu_id')->onDelete('cascade');
            $table->foreignId('uxr_rol_id')->constrained('sec_roles', 'rol_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['uxr_urbanizacion_usuario_id', 'uxr_rol_id'], 'uq_sec_usuario_roles');
        });

        // 4. Inmuebles (Villas, Casas, Departamentos, Lotes)
        Schema::create('core_inmuebles', function (Blueprint $table) {
            $table->id('inm_id')->comment('Identificador unico del inmueble');
            $table->uuid('inm_urbanizacion_id');
            $table->foreign('inm_urbanizacion_id')->references('urb_id')->on('core_urbanizaciones')->onDelete('cascade');
            $table->string('inm_manzana_bloque', 30)->comment('Manzana, Bloque o Sector');
            $table->string('inm_numero_villa', 30)->comment('Numero de Villa, Casa o Departamento');
            $table->string('inm_tipo', 30)->default('CASA')->comment('CASA, DEPARTAMENTO, LOTE_VACIO, COMERCIAL');
            $table->string('inm_estado', 30)->default('HABITADO')->comment('HABITADO, DESOCUPADO, EN_CONSTRUCCION');
            $table->integer('inm_alicuota_porcentaje')->nullable()->comment('Porcentaje de alicuota base (x100)');
            $table->text('inm_observaciones')->nullable();
            $table->boolean('inm_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['inm_urbanizacion_id', 'inm_manzana_bloque', 'inm_numero_villa'], 'uq_core_inmuebles_ubicacion');
            $table->index('inm_urbanizacion_id', 'idx_core_inmuebles_urbanizacion');
        });

        // 5. Residentes y Propietarios asignados a Inmuebles
        Schema::create('core_inmueble_residentes', function (Blueprint $table) {
            $table->id('res_id');
            $table->foreignId('res_inmueble_id')->constrained('core_inmuebles', 'inm_id')->onDelete('cascade');
            $table->foreignId('res_persona_id')->constrained('core_personas', 'per_id')->onDelete('cascade');
            $table->string('res_tipo_relacion', 30)->default('PROPIETARIO')->comment('PROPIETARIO, ARRENDATARIO, FAMILIAR, EMPLEADO_DOMESTICO');
            $table->boolean('res_es_principal')->default(false)->comment('Contacto principal del inmueble');
            $table->boolean('res_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['res_inmueble_id', 'res_persona_id'], 'uq_core_inmueble_residentes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('core_inmueble_residentes');
        Schema::dropIfExists('core_inmuebles');
        Schema::dropIfExists('sec_usuario_roles');
        Schema::dropIfExists('sec_urbanizacion_usuarios');
        Schema::dropIfExists('core_urbanizaciones');
    }
};
