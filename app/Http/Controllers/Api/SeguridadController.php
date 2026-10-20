<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Core\Persona;
use App\Models\Security\Permiso;
use App\Models\Security\Rol;
use App\Models\Security\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SeguridadController extends Controller
{
    /**
     * Listado de Usuarios con sus Roles por Urbanización
     */
    public function usuarios(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $query = User::with(['persona', 'urbanizaciones'])
            ->whereHas('urbanizaciones', function ($q) use ($urbanizacionId) {
                $q->where('core_urbanizaciones.urb_id', $urbanizacionId);
            })
            ->orWhere('usr_es_superadmin', true);

        if ($request->filled('buscar')) {
            $b = trim($request->input('buscar'));
            $query->where(function ($q) use ($b) {
                $q->where('usr_name', 'ilike', "%{$b}%")
                  ->orWhere('usr_email', 'ilike', "%{$b}%");
            });
        }

        $usuarios = $query->orderBy('usr_name')->get();

        // Enriquecer cada usuario con sus roles y urbanizaciones asignadas
        foreach ($usuarios as $u) {
            $userRoles = DB::table('sec_usuario_roles')
                ->join('sec_urbanizacion_usuarios', 'sec_usuario_roles.uxr_urbanizacion_usuario_id', '=', 'sec_urbanizacion_usuarios.uxu_id')
                ->join('sec_roles', 'sec_usuario_roles.uxr_rol_id', '=', 'sec_roles.rol_id')
                ->where('sec_urbanizacion_usuarios.uxu_user_id', $u->usr_id)
                ->select('sec_roles.rol_id', 'sec_roles.rol_nombre', 'sec_urbanizacion_usuarios.uxu_urbanizacion_id')
                ->get();

            $u->roles_list = $userRoles;
            $u->roles_ids = $userRoles->pluck('rol_id')->unique()->values()->all();
            $u->urbanizaciones_ids = $u->urbanizaciones->pluck('urb_id')->values()->all();
        }

        return response()->json([
            'success' => true,
            'total' => $usuarios->count(),
            'data' => $usuarios,
        ]);
    }

    /**
     * Crear un nuevo Usuario y Asignarle Roles en la Urbanización
     */
    public function storeUsuario(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $validated = $request->validate([
            'identificacion' => 'required|string',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'email' => 'required|email|unique:sec_users,usr_email',
            'password' => 'required|string|min:6',
            'rol_id' => 'nullable|exists:sec_roles,rol_id',
            'rol_ids' => 'nullable|array',
            'rol_ids.*' => 'exists:sec_roles,rol_id',
            'urbanizacion_ids' => 'nullable|array',
            'urbanizacion_ids.*' => 'exists:core_urbanizaciones,urb_id',
            'es_superadmin' => 'boolean',
        ]);

        $rolesSeleccionados = !empty($validated['rol_ids']) 
            ? $validated['rol_ids'] 
            : (!empty($validated['rol_id']) ? [$validated['rol_id']] : [2]);

        $urbanizacionesSeleccionadas = !empty($validated['urbanizacion_ids']) 
            ? $validated['urbanizacion_ids'] 
            : ($urbanizacionId ? [$urbanizacionId] : []);

        // 1. Persona física en core_personas
        $persona = Persona::updateOrCreate(
            ['per_identificacion' => $validated['identificacion']],
            [
                'per_nombres' => $validated['nombres'],
                'per_apellidos' => $validated['apellidos'],
                'per_email' => $validated['email'],
            ]
        );

        // 2. Cuenta de usuario en sec_users
        $user = User::create([
            'usr_persona_id' => $persona->per_id,
            'usr_name' => "{$validated['nombres']} {$validated['apellidos']}",
            'usr_email' => $validated['email'],
            'usr_password' => Hash::make($validated['password']),
            'usr_es_superadmin' => $validated['es_superadmin'] ?? false,
            'usr_activo' => true,
        ]);

        // 3. Vincular a las urbanizaciones seleccionadas y asignar roles
        foreach ($urbanizacionesSeleccionadas as $idx => $urbId) {
            $uxuId = DB::table('sec_urbanizacion_usuarios')->insertGetId([
                'uxu_urbanizacion_id' => $urbId,
                'uxu_user_id' => $user->usr_id,
                'uxu_es_predeterminada' => ($idx === 0),
                'uxu_activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ], 'uxu_id');

            foreach ($rolesSeleccionados as $rolId) {
                DB::table('sec_usuario_roles')->insert([
                    'uxr_urbanizacion_usuario_id' => $uxuId,
                    'uxr_rol_id' => $rolId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        AuditLogger::log('USER_CREATED', 'sec_users', $user->usr_id, [
            'email' => $user->usr_email,
            'rol_id' => $validated['rol_id']
        ], null, $urbanizacionId);

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado exitosamente con credenciales de acceso.',
            'data' => $user->load('persona'),
        ], 201);
    }

    /**
     * Catálogo de Roles y su Matriz de Permisos
     */
    public function roles(): JsonResponse
    {
        $roles = Rol::with('permisos')->orderBy('rol_nombre')->get();
        $permisos = Permiso::orderBy('prm_modulo')->orderBy('prm_accion')->get();

        return response()->json([
            'success' => true,
            'roles' => $roles,
            'permisos' => $permisos,
        ]);
    }

    /**
     * Actualizar Permisos de un Rol
     */
    public function actualizarPermisosRol(Request $request, int $rolId): JsonResponse
    {
        $rol = Rol::findOrFail($rolId);

        $validated = $request->validate([
            'permiso_ids' => 'required|array',
            'permiso_ids.*' => 'exists:sec_permisos,prm_id',
        ]);

        $rol->permisos()->sync($validated['permiso_ids']);

        AuditLogger::log('ROLE_PERMISSIONS_UPDATED', 'sec_roles', $rol->rol_id, [
            'permisos' => $validated['permiso_ids']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Matriz de permisos actualizada exitosamente para el rol.',
            'data' => $rol->load('permisos'),
        ]);
    }

    /**
     * Trazabilidad CDC de Auditoría
     */
    public function auditoriaLogs(Request $request): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $query = DB::table('aud_trazabilidad')
            ->where(function ($q) use ($urbanizacionId) {
                $q->where('aud_urbanizacion_id', $urbanizacionId)
                  ->orWhereNull('aud_urbanizacion_id');
            });

        if ($request->filled('evento')) {
            $query->where('aud_evento', $request->input('evento'));
        }

        if ($request->filled('tabla')) {
            $query->where('aud_tabla', $request->input('tabla'));
        }

        if ($request->filled('buscar')) {
            $b = trim($request->input('buscar'));
            $query->where(function ($q) use ($b) {
                $q->where('aud_user_nombre', 'ilike', "%{$b}%")
                  ->orWhere('aud_ip_address', 'ilike', "%{$b}%")
                  ->orWhere('aud_registro_id', 'ilike', "%{$b}%");
            });
        }

        $logs = $query->orderBy('aud_created_at', 'desc')->paginate(30);

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Actualizar datos de un Usuario existente
     */
    public function updateUsuario(Request $request, int $id): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'identificacion' => 'nullable|string',
            'nombres' => 'required|string',
            'apellidos' => 'required|string',
            'email' => 'required|email|unique:sec_users,usr_email,' . $user->usr_id . ',usr_id',
            'password' => 'nullable|string|min:6',
            'rol_id' => 'nullable|exists:sec_roles,rol_id',
            'rol_ids' => 'nullable|array',
            'rol_ids.*' => 'exists:sec_roles,rol_id',
            'urbanizacion_ids' => 'nullable|array',
            'urbanizacion_ids.*' => 'exists:core_urbanizaciones,urb_id',
            'es_superadmin' => 'boolean',
            'activo' => 'boolean',
        ]);

        // Actualizar o crear persona vinculada
        if (!empty($validated['identificacion'])) {
            $persona = Persona::updateOrCreate(
                ['per_identificacion' => $validated['identificacion']],
                [
                    'per_nombres' => $validated['nombres'],
                    'per_apellidos' => $validated['apellidos'],
                    'per_email' => $validated['email'],
                ]
            );
            $user->usr_persona_id = $persona->per_id;
        }

        $user->usr_name = trim($validated['nombres'] . ' ' . $validated['apellidos']);
        $user->usr_email = $validated['email'];
        if (!empty($validated['password'])) {
            $user->usr_password = Hash::make($validated['password']);
        }
        if (isset($validated['es_superadmin'])) {
            $user->usr_es_superadmin = $validated['es_superadmin'];
        }
        if (isset($validated['activo'])) {
            $user->usr_activo = $validated['activo'];
        }
        $user->save();

        // Sincronizar Urbanizaciones y Roles asignados
        $rolesSeleccionados = !empty($validated['rol_ids']) 
            ? $validated['rol_ids'] 
            : (!empty($validated['rol_id']) ? [$validated['rol_id']] : []);

        $urbanizacionesSeleccionadas = isset($validated['urbanizacion_ids']) 
            ? $validated['urbanizacion_ids'] 
            : ($urbanizacionId ? [$urbanizacionId] : []);

        if (!empty($urbanizacionesSeleccionadas) || !empty($rolesSeleccionados)) {
            // Obtener uxu_ids actuales para limpiar roles antiguos
            $oldUxuIds = DB::table('sec_urbanizacion_usuarios')
                ->where('uxu_user_id', $user->usr_id)
                ->pluck('uxu_id');

            DB::table('sec_usuario_roles')->whereIn('uxr_urbanizacion_usuario_id', $oldUxuIds)->delete();
            DB::table('sec_urbanizacion_usuarios')->where('uxu_user_id', $user->usr_id)->delete();

            foreach ($urbanizacionesSeleccionadas as $idx => $urbId) {
                $uxuId = DB::table('sec_urbanizacion_usuarios')->insertGetId([
                    'uxu_urbanizacion_id' => $urbId,
                    'uxu_user_id' => $user->usr_id,
                    'uxu_es_predeterminada' => ($idx === 0),
                    'uxu_activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'uxu_id');

                foreach ($rolesSeleccionados as $rolId) {
                    DB::table('sec_usuario_roles')->insert([
                        'uxr_urbanizacion_usuario_id' => $uxuId,
                        'uxr_rol_id' => $rolId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        AuditLogger::log('USER_UPDATED', 'sec_users', $user->usr_id, [
            'email' => $user->usr_email,
            'name' => $user->usr_name,
        ], null, $urbanizacionId);

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado exitosamente.',
            'data' => $user->load('persona'),
        ]);
    }

    /**
     * Desactivar cuenta de usuario
     */
    public function destroyUsuario(Request $request, int $id): JsonResponse
    {
        $urbanizacionId = $request->header('X-Urbanizacion-Id') 
            ?: (session('active_urbanizacion_id') ?: $request->input('urbanizacion_id'));

        $user = User::findOrFail($id);
        $user->usr_activo = false;
        $user->save();

        AuditLogger::log('USER_DEACTIVATED', 'sec_users', $user->usr_id, null, null, $urbanizacionId);

        return response()->json([
            'success' => true,
            'message' => 'Usuario desactivado exitosamente del sistema.',
        ]);
    }

}
