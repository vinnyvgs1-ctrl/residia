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
        // 1. Catálogo Maestro de Vistas / Pantallas del Sistema
        Schema::create('sec_vistas', function (Blueprint $table) {
            $table->id('vis_id')->comment('Identificador unico de la vista');
            $table->string('vis_codigo', 60)->unique()->comment('Slug identificador (ej. garita, admin.urbanizaciones, admin.inmuebles, sec.usuarios, sec.roles, sec.vistas, sec.auditoria, rep.accesos)');
            $table->string('vis_nombre', 100)->comment('Nombre visible de la pantalla');
            $table->string('vis_categoria', 50)->comment('Categoria de agrupacion: OPERACION, ADMINISTRACION, SEGURIDAD, REPORTES');
            $table->string('vis_icono', 40)->default('Layout')->comment('Nombre del icono lucide');
            $table->string('vis_descripcion', 255)->nullable();
            $table->integer('vis_orden')->default(0)->comment('Orden de aparicion en menu');
            $table->boolean('vis_es_menu')->default(true)->comment('Si se muestra como enlace de navegacion');
            $table->boolean('vis_activo')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['vis_categoria', 'vis_orden'], 'idx_sec_vistas_categoria_orden');
        });

        // 2. Asignación de Vistas a Roles (Matriz de Pantallas Autorizadas)
        Schema::create('sec_rol_vistas', function (Blueprint $table) {
            $table->id('rxv_id');
            $table->foreignId('rxv_rol_id')->constrained('sec_roles', 'rol_id')->onDelete('cascade');
            $table->foreignId('rxv_vista_id')->constrained('sec_vistas', 'vis_id')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['rxv_rol_id', 'rxv_vista_id'], 'uq_sec_rol_vistas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_rol_vistas');
        Schema::dropIfExists('sec_vistas');
    }
};
