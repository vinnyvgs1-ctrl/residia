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
        // 1. Roles del Sistema
        Schema::create('sec_roles', function (Blueprint $table) {
            $table->id('rol_id')->comment('Identificador unico del rol');
            $table->string('rol_codigo', 50)->unique()->comment('Slug del rol (ej. superadmin, admin_urbanizacion, guardia_garita, residente)');
            $table->string('rol_nombre', 100)->comment('Nombre legible del rol');
            $table->string('rol_descripcion', 255)->nullable()->comment('Descripcion del alcance y responsabilidades del rol');
            $table->boolean('rol_es_sistema')->default(false)->comment('Protege contra eliminacion si es rol base');
            $table->boolean('rol_activo')->default(true)->comment('Estado del rol');
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Permisos Granulares de 5 Dimensiones (read, write, update, delete, print)
        Schema::create('sec_permisos', function (Blueprint $table) {
            $table->id('prm_id')->comment('Identificador unico del permiso');
            $table->string('prm_modulo', 50)->comment('Modulo funcional (urbanizaciones, inmuebles, personas, accesos, reportes, auditoria, temas)');
            $table->string('prm_accion', 20)->comment('Accion permitida: read, write, update, delete, print');
            $table->string('prm_slug', 80)->unique()->comment('Identificador unico compuesto (ej. accesos.read, accesos.print, reportes.print)');
            $table->string('prm_descripcion', 255)->nullable()->comment('Descripcion para el panel de asignacion');
            $table->timestamps();

            $table->index(['prm_modulo', 'prm_accion'], 'idx_sec_permisos_modulo_accion');
        });

        // 3. Tabla Intermedia Rol - Permisos
        Schema::create('sec_rol_permisos', function (Blueprint $table) {
            $table->id('rxp_id');
            $table->foreignId('rxp_rol_id')->constrained('sec_roles', 'rol_id')->onDelete('cascade');
            $table->foreignId('rxp_permiso_id')->constrained('sec_permisos', 'prm_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['rxp_rol_id', 'rxp_permiso_id'], 'uq_sec_rol_permisos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_rol_permisos');
        Schema::dropIfExists('sec_permisos');
        Schema::dropIfExists('sec_roles');
    }
};
