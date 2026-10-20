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
        // 1. Padrón Central de Identidades Físicas (Party Model)
        Schema::create('core_personas', function (Blueprint $table) {
            $table->id('per_id')->comment('Identificador unico de la persona fisica');
            $table->string('per_identificacion', 30)->unique()->comment('Cedula de identidad, Pasaporte o RUC');
            $table->string('per_tipo_identificacion', 20)->default('CEDULA')->comment('CEDULA, PASAPORTE, RUC');
            $table->string('per_nombres', 100)->comment('Nombres de la persona');
            $table->string('per_apellidos', 100)->comment('Apellidos de la persona');
            $table->string('per_telefono', 30)->nullable()->comment('Telefono o celular principal');
            $table->string('per_email', 150)->nullable()->comment('Correo electronico de contacto personal');
            $table->string('per_foto_url', 255)->nullable()->comment('Ruta a foto de perfil o captura de documento');
            $table->boolean('per_es_vetado')->default(false)->comment('Alerta roja de seguridad: persona vetada');
            $table->text('per_motivo_veto')->nullable()->comment('Justificacion del veto en caso de estar activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('per_identificacion', 'idx_core_personas_identificacion');
            $table->index(['per_apellidos', 'per_nombres'], 'idx_core_personas_nombre_completo');
        });

        // 2. Modificación / Reemplazo de la tabla de usuarios de Laravel por sec_users
        Schema::dropIfExists('users');
        Schema::create('sec_users', function (Blueprint $table) {
            $table->id('usr_id')->comment('Identificador unico de la cuenta de login');
            $table->foreignId('usr_persona_id')->nullable()->constrained('core_personas', 'per_id')->nullOnDelete()->comment('Vinculo opcional 1 a 1 con la persona fisica');
            $table->string('usr_name', 100)->comment('Nombre para mostrar');
            $table->string('usr_email', 150)->unique()->comment('Email para autenticacion en el sistema');
            $table->timestamp('usr_email_verified_at')->nullable();
            $table->string('usr_password')->comment('Hash de contrasena Bcrypt o Argon2');
            $table->boolean('usr_es_superadmin')->default(false)->comment('Acceso irrestricto global a todas las urbanizaciones');
            $table->boolean('usr_activo')->default(true)->comment('Estado de la cuenta: activa o suspendida');
            $table->jsonb('usr_preferencias')->nullable()->comment('Preferencias de interfaz (ej. tema nocturno, notificaciones)');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('usr_email', 'idx_sec_users_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sec_users');
        Schema::dropIfExists('core_personas');
    }
};
