<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ==========================================
        // 1. TEMAS OFICIALES ROBLEDAL CONNECTING
        // ==========================================
        $temaDark = [
            '--bg-primary' => '#323637',
            '--bg-surface' => '#2B2E2F',
            '--bg-card' => '#3F4445',
            '--bg-hover' => '#4B5153',
            '--text-primary' => '#FFFFFF',
            '--text-secondary' => '#9BA3A7',
            '--text-muted' => '#6E777B',
            '--color-primary' => '#72C1A5',
            '--color-primary-hover' => '#5EB093',
            '--color-primary-subtle' => 'rgba(114, 193, 165, 0.15)',
            '--color-accent' => '#FFFFFF',
            '--color-warning' => '#F59E0B',
            '--color-danger' => '#EF4444',
            '--color-success' => '#72C1A5',
            '--border-subtle' => 'rgba(255, 255, 255, 0.10)',
            '--border-focus' => '#72C1A5',
            '--shadow-elevation' => '0 10px 25px -5px rgba(0, 0, 0, 0.5)'
        ];

        $temaLight = [
            '--bg-primary' => '#F8FAFB',
            '--bg-surface' => '#FFFFFF',
            '--bg-card' => '#FFFFFF',
            '--bg-hover' => '#F1F5F7',
            '--text-primary' => '#1E2223',
            '--text-secondary' => '#636C70',
            '--text-muted' => '#9BA3A7',
            '--color-primary' => '#72C1A5',
            '--color-primary-hover' => '#5EB093',
            '--color-primary-subtle' => 'rgba(114, 193, 165, 0.12)',
            '--color-accent' => '#323637',
            '--color-warning' => '#D97706',
            '--color-danger' => '#DC2626',
            '--color-success' => '#059669',
            '--border-subtle' => '#E4E8EC',
            '--border-focus' => '#72C1A5',
            '--shadow-elevation' => '0 4px 20px -2px rgba(50, 54, 55, 0.08)'
        ];

        DB::table('sec_temas')->updateOrInsert(
            ['tem_codigo' => 'robledal-dark'],
            [
                'tem_nombre' => 'Robledal Dark (Oficial)',
                'tem_descripcion' => 'Modo oscuro minimalista con antracita #323637 y menta #72C1A5',
                'tem_variables_css' => json_encode($temaDark),
                'tem_es_oscuro' => true,
                'tem_es_sistema' => true,
                'tem_activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('sec_temas')->updateOrInsert(
            ['tem_codigo' => 'robledal-light'],
            [
                'tem_nombre' => 'Robledal Light Minimal',
                'tem_descripcion' => 'Modo claro ejecutivo con fondo limpio y acento menta #72C1A5',
                'tem_variables_css' => json_encode($temaLight),
                'tem_es_oscuro' => false,
                'tem_es_sistema' => true,
                'tem_activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $temaDarkRow = DB::table('sec_temas')->where('tem_codigo', 'robledal-dark')->first();

        // ==========================================
        // 2. ROLES DEL SISTEMA
        // ==========================================
        $roles = [
            ['rol_codigo' => 'superadmin', 'rol_nombre' => 'Super Administrador Global', 'rol_descripcion' => 'Acceso total y configuración de todas las urbanizaciones'],
            ['rol_codigo' => 'admin_urbanizacion', 'rol_nombre' => 'Administrador de Urbanización', 'rol_descripcion' => 'Gestión completa de su urbanización asignada'],
            ['rol_codigo' => 'guardia_garita', 'rol_nombre' => 'Operador de Garita / Seguridad', 'rol_descripcion' => 'Control de entradas, salidas y novedades de accesos'],
            ['rol_codigo' => 'residente', 'rol_nombre' => 'Residente / Copropietario', 'rol_descripcion' => 'Pre-autorización de visitas y consulta de estado propio'],
        ];

        foreach ($roles as $r) {
            DB::table('sec_roles')->updateOrInsert(
                ['rol_codigo' => $r['rol_codigo']],
                [
                    'rol_nombre' => $r['rol_nombre'],
                    'rol_descripcion' => $r['rol_descripcion'],
                    'rol_es_sistema' => true,
                    'rol_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // ==========================================
        // 3. PERMISOS DE 5 DIMENSIONES (read, write, update, delete, print)
        // ==========================================
        $modulos = ['urbanizaciones', 'inmuebles', 'personas', 'accesos', 'reportes', 'auditoria', 'temas'];
        $acciones = [
            'read' => 'Lectura y visualización',
            'write' => 'Creación y registro',
            'update' => 'Modificación y edición',
            'delete' => 'Eliminación lógica',
            'print' => 'Descarga e impresión de reportes/documentos',
        ];

        $permisoIds = [];
        foreach ($modulos as $mod) {
            foreach ($acciones as $acc => $accDesc) {
                $slug = "{$mod}.{$acc}";
                DB::table('sec_permisos')->updateOrInsert(
                    ['prm_slug' => $slug],
                    [
                        'prm_modulo' => $mod,
                        'prm_accion' => $acc,
                        'prm_descripcion' => "{$accDesc} en {$mod}",
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
                $p = DB::table('sec_permisos')->where('prm_slug', $slug)->first();
                $permisoIds[$slug] = $p->prm_id;
            }
        }

        // Asignar todos los permisos al rol admin_urbanizacion
        $adminRol = DB::table('sec_roles')->where('rol_codigo', 'admin_urbanizacion')->first();
        foreach ($permisoIds as $pId) {
            DB::table('sec_rol_permisos')->updateOrInsert(
                ['rxp_rol_id' => $adminRol->rol_id, 'rxp_permiso_id' => $pId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // Permisos para el Guardia de Garita
        $guardiaRol = DB::table('sec_roles')->where('rol_codigo', 'guardia_garita')->first();
        $guardiaPermisos = [
            $permisoIds['accesos.read'],
            $permisoIds['accesos.write'],
            $permisoIds['accesos.update'],
            $permisoIds['personas.read'],
            $permisoIds['personas.write'],
            $permisoIds['inmuebles.read'],
            $permisoIds['reportes.read'],
        ];
        foreach ($guardiaPermisos as $pId) {
            DB::table('sec_rol_permisos')->updateOrInsert(
                ['rxp_rol_id' => $guardiaRol->rol_id, 'rxp_permiso_id' => $pId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        // ==========================================
        // 4. PERSONA Y USUARIO SUPERADMIN
        // ==========================================
        $personaAdminId = DB::table('core_personas')->insertGetId([
            'per_identificacion' => '1799999999',
            'per_tipo_identificacion' => 'CEDULA',
            'per_nombres' => 'Administrador',
            'per_apellidos' => 'Robledal',
            'per_telefono' => '+593999999999',
            'per_email' => 'admin@robledal.com',
            'per_es_vetado' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'per_id');

        $userAdminId = DB::table('sec_users')->insertGetId([
            'usr_persona_id' => $personaAdminId,
            'usr_name' => 'Admin Robledal',
            'usr_email' => 'admin@robledal.com',
            'usr_password' => Hash::make('Admin2026.*'),
            'usr_es_superadmin' => true,
            'usr_activo' => true,
            'usr_preferencias' => json_encode(['theme' => 'robledal-dark']),
            'created_at' => now(),
            'updated_at' => now(),
        ], 'usr_id');

        // ==========================================
        // 5. URBANIZACIÓN PILOTO (ROBLEDAL)
        // ==========================================
        $urbId = (string) Str::uuid();
        DB::table('core_urbanizaciones')->insert([
            'urb_id' => $urbId,
            'urb_codigo' => 'URB-ROBLEDAL-01',
            'urb_nombre' => 'Urbanización Piloto Robledal',
            'urb_ruc' => '1790000000001',
            'urb_direccion' => 'Av. Interoceánica Km 12, Cumbayá',
            'urb_ciudad' => 'Quito',
            'urb_telefono' => '022999999',
            'urb_tema_id' => $temaDarkRow->tem_id,
            'urb_configuracion' => json_encode([
                'tiempo_maximo_visita_minutos' => 240, // 4 horas
                'tolerancia_salida_minutos' => 15,
                'exigir_cedula_acompanantes' => true,
            ]),
            'urb_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Asignar el SuperAdmin a la Urbanización Piloto
        $uxuId = DB::table('sec_urbanizacion_usuarios')->insertGetId([
            'uxu_urbanizacion_id' => $urbId,
            'uxu_user_id' => $userAdminId,
            'uxu_es_predeterminada' => true,
            'uxu_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'uxu_id');

        $superRol = DB::table('sec_roles')->where('rol_codigo', 'superadmin')->first();
        DB::table('sec_usuario_roles')->insert([
            'uxr_urbanizacion_usuario_id' => $uxuId,
            'uxr_rol_id' => $superRol->rol_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Garita Principal
        DB::table('op_garitas')->insert([
            'gar_urbanizacion_id' => $urbId,
            'gar_nombre' => 'Garita Principal (Acceso Norte)',
            'gar_ubicacion' => 'Entrada Av. Interoceánica',
            'gar_permite_vehicular' => true,
            'gar_permite_peatonal' => true,
            'gar_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Inmueble Piloto
        $inmId = DB::table('core_inmuebles')->insertGetId([
            'inm_urbanizacion_id' => $urbId,
            'inm_manzana_bloque' => 'Manzana A',
            'inm_numero_villa' => 'Villa 01',
            'inm_tipo' => 'CASA',
            'inm_estado' => 'HABITADO',
            'inm_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'inm_id');

        // Auditoría Inicial del Despliegue
        DB::table('aud_trazabilidad')->insert([
            'aud_urbanizacion_id' => $urbId,
            'aud_user_id' => $userAdminId,
            'aud_user_nombre' => 'Admin Robledal',
            'aud_evento' => 'INITIAL_SYSTEM_SEED',
            'aud_tabla' => 'core_urbanizaciones',
            'aud_registro_id' => $urbId,
            'aud_new_values' => json_encode(['nombre' => 'Urbanización Piloto Robledal', 'codigo' => 'URB-ROBLEDAL-01']),
            'aud_ip_address' => '127.0.0.1',
            'aud_user_agent' => 'CLI DatabaseSeeder Seed',
            'aud_created_at' => now(),
        ]);
    }
}
