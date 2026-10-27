<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'residia:clean 
                            {--mode=transaccional : Modo de limpieza: "transaccional" (limpia visitas, cobros, bitacora) o "total" (limpia todo y deja solo admin@robledal.co)}
                            {--force : Ejecutar sin solicitar confirmación interactiva}';

    /**
     * The console command description.
     */
    protected $description = 'Limpia la base de datos de Residia para reiniciar el prototipo o iniciar con datos reales';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mode = strtolower((string) $this->option('mode'));

        if (! in_array($mode, ['transaccional', 'total'])) {
            $this->error("El modo especificado '{$mode}' no es válido. Use --mode=transaccional o --mode=total.");
            return Command::FAILURE;
        }

        $this->warn("========================================================================");
        $this->warn("             RESIDIA CRM • HERRAMIENTA DE LIMPIEZA DE DATOS             ");
        $this->warn("========================================================================");

        if ($mode === 'transaccional') {
            $this->info("MODO SELECCIONADO: TRANSACCIONAL (Reset de Pruebas)");
            $this->line("- Se conservarán: Urbanizaciones, Inmuebles, Habitantes, Flota, Proveedores, Amenidades y Cuentas.");
            $this->line("- Se eliminarán:  Visitas en garita, Pases QR, Cobros de alícuotas, Reservas, Paquetería, Novedades y Auditoría CDC.");
        } else {
            $this->error("MODO SELECCIONADO: TOTAL (Lanzamiento en Blanco Cero)");
            $this->line("- Se conservará únicamente: SuperAdmin (admin@robledal.co), Roles, Permisos y Temas.");
            $this->line("- Se eliminarán:  Todas las urbanizaciones, casas, residentes, vehículos y movimientos.");
        }

        $this->newLine();

        if (! $this->option('force')) {
            if (! $this->confirm("¿Está completamente seguro de continuar con la limpieza en modo [{$mode}]?", false)) {
                $this->info("Operación cancelada por el usuario.");
                return Command::SUCCESS;
            }
        }

        $this->info("Iniciando proceso de vaciado seguro...");

        try {
            DB::beginTransaction();

            if ($mode === 'transaccional') {
                $this->limpiarTransaccional();
            } else {
                $this->limpiarTotal();
            }

            DB::commit();

            // Limpiar caché de Laravel
            $this->callSilent('cache:clear');
            $this->info("✓ Caché del sistema vaciada exitosamente.");

            $this->newLine();
            $this->info("========================================================================");
            $this->info("     ¡BASE DE DATOS LIMPIADA EXITOSAMENTE [MODO: " . strtoupper($mode) . "]!    ");
            $this->info("========================================================================");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error al ejecutar la limpieza: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Limpia únicamente datos de movimiento y operación manteniendo la estructura física
     */
    protected function limpiarTransaccional(): void
    {
        $tablasTransaccionales = [
            'op_control_accesos',
            'acc_aperturas_contingencia',
            'op_invitaciones_qr',
            'fin_obligaciones_alicuotas',
            'op_reservas_amenidades',
            'op_paqueteria',
            'op_novedades_turno',
            'op_ordenes_mantenimiento',
            'op_votos_emitidos',
            'op_biometria_sync_logs',
            'aud_trazabilidad',
            'aud_reportes_descargas',
            'sessions',
        ];

        foreach ($tablasTransaccionales as $tabla) {
            if ($this->tablaExiste($tabla)) {
                DB::statement("TRUNCATE TABLE {$tabla} CASCADE;");
                $this->line("  ✓ Tabla vaciada: {$tabla}");
            }
        }
    }

    /**
     * Limpia toda la base de datos dejando únicamente la cuenta maestra admin@robledal.co
     */
    protected function limpiarTotal(): void
    {
        // 1. Limpiar transaccional
        $this->limpiarTransaccional();

        // 2. Limpiar catálogos operativos
        $tablasOperativas = [
            'op_biometria_residentes',
            'op_lista_negra',
            'op_comunicados',
            'op_votaciones',
            'op_areas_sociales',
            'op_activos_infraestructura',
            'op_proveedores',
            'op_garita_dispositivos',
            'op_garitas',
            'op_push_subscriptions',
            'core_inmueble_vehiculos',
            'core_inmueble_residentes',
            'core_inmuebles',
            'sec_urbanizacion_usuarios',
            'core_urbanizaciones',
        ];

        foreach ($tablasOperativas as $tabla) {
            if ($this->tablaExiste($tabla)) {
                DB::statement("TRUNCATE TABLE {$tabla} CASCADE;");
                $this->line("  ✓ Tabla vaciada: {$tabla}");
            }
        }

        // 3. Limpiar usuarios y personas, preservando admin@robledal.co
        if ($this->tablaExiste('sec_users') && $this->tablaExiste('core_personas')) {
            $adminUser = DB::table('sec_users')->where('usr_email', 'admin@robledal.co')->first();
            $adminPersonaId = $adminUser?->usr_persona_id;

            if ($adminUser) {
                // Eliminar roles de usuarios no-admin
                DB::table('sec_usuario_roles')->where('uro_user_id', '!=', $adminUser->usr_id)->delete();
                // Eliminar usuarios no-admin
                DB::table('sec_users')->where('usr_id', '!=', $adminUser->usr_id)->delete();
                $this->line("  ✓ Cuentas de usuario depuradas (preservado: admin@robledal.co)");

                // Eliminar personas no-admin
                if ($adminPersonaId) {
                    DB::table('core_personas')->where('per_id', '!=', $adminPersonaId)->delete();
                } else {
                    DB::table('core_personas')->truncate();
                }
                $this->line("  ✓ Padrón de personas físicas reiniciado.");
            }
        }
    }

    /**
     * Verifica si una tabla existe en PostgreSQL
     */
    protected function tablaExiste(string $tabla): bool
    {
        return DB::selectOne("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?", [$tabla]) !== null;
    }
}
