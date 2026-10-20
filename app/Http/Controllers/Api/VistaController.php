<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Security\Rol;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VistaController extends Controller
{
    /**
     * Catálogo de Vistas agrupadas por Categoría
     */
    public function index(): JsonResponse
    {
        $vistas = DB::table('sec_vistas')
            ->whereNull('deleted_at')
            ->where('vis_activo', true)
            ->orderBy('vis_categoria')
            ->orderBy('vis_orden')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vistas,
            'agrupadas' => $vistas->groupBy('vis_categoria'),
        ]);
    }

    /**
     * Vistas autorizadas para el usuario autenticado según sus roles en la urbanización activa
     */
    public function misVistas(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'No autenticado'], 401);
        }

        $vistasQuery = DB::table('sec_vistas')
            ->whereNull('deleted_at')
            ->where('vis_activo', true)
            ->where('vis_es_menu', true);

        // Si no es SuperAdmin, filtrar por los roles en la urbanización
        if (!$user->usr_es_superadmin) {
            $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

            $uxu = DB::table('sec_urbanizacion_usuarios')
                ->where('uxu_user_id', $user->usr_id)
                ->where('uxu_urbanizacion_id', $urbanizacionId)
                ->where('uxu_activo', true)
                ->first();

            $rolIds = $uxu ? DB::table('sec_usuario_roles')
                ->where('uxr_urbanizacion_usuario_id', $uxu->uxu_id)
                ->pluck('uxr_rol_id')
                ->toArray() : [];

            $vistaIds = DB::table('sec_rol_vistas')
                ->whereIn('rxv_rol_id', $rolIds)
                ->pluck('rxv_vista_id')
                ->unique()
                ->toArray();

            $vistasQuery->whereIn('vis_id', $vistaIds);
        }

        $vistas = $vistasQuery->orderBy('vis_categoria')->orderBy('vis_orden')->get();

        return response()->json([
            'success' => true,
            'es_superadmin' => (bool) $user->usr_es_superadmin,
            'vistas' => $vistas,
            'codigos' => $vistas->pluck('vis_codigo')->toArray(),
        ]);
    }

    /**
     * Vistas autorizadas para un rol específico
     */
    public function vistasPorRol(int $rolId): JsonResponse
    {
        $rol = Rol::findOrFail($rolId);

        $vistaIds = DB::table('sec_rol_vistas')
            ->where('rxv_rol_id', $rolId)
            ->pluck('rxv_vista_id')
            ->toArray();

        return response()->json([
            'success' => true,
            'rol' => $rol,
            'vista_ids' => $vistaIds,
        ]);
    }

    /**
     * Asignar Vistas a un Rol
     */
    public function asignarVistasRol(Request $request, int $rolId): JsonResponse
    {
        $rol = Rol::findOrFail($rolId);

        $validated = $request->validate([
            'vista_ids' => 'required|array',
            'vista_ids.*' => 'exists:sec_vistas,vis_id',
        ]);

        DB::table('sec_rol_vistas')->where('rxv_rol_id', $rolId)->delete();

        $inserts = array_map(function ($vid) use ($rolId) {
            return [
                'rxv_rol_id' => $rolId,
                'rxv_vista_id' => $vid,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $validated['vista_ids']);

        DB::table('sec_rol_vistas')->insert($inserts);

        AuditLogger::log('ROLE_VIEWS_UPDATED', 'sec_roles', $rol->rol_id, [
            'vistas' => $validated['vista_ids']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vistas asignadas exitosamente al rol.',
        ]);
    }

    /**
     * Crear una nueva Vista en el Catálogo del Sistema
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'vis_codigo' => 'required|string|max:60|unique:sec_vistas,vis_codigo',
            'vis_nombre' => 'required|string|max:100',
            'vis_categoria' => 'required|string|in:ADMINISTRACION,OPERACION,REPORTES,SEGURIDAD',
            'vis_icono' => 'nullable|string|max:40',
            'vis_descripcion' => 'nullable|string',
            'vis_orden' => 'nullable|integer',
            'vis_es_menu' => 'boolean',
            'vis_activo' => 'boolean',
        ]);

        $visId = DB::table('sec_vistas')->insertGetId([
            'vis_codigo' => trim($validated['vis_codigo']),
            'vis_nombre' => trim($validated['vis_nombre']),
            'vis_categoria' => $validated['vis_categoria'],
            'vis_icono' => $validated['vis_icono'] ?? 'LayoutDashboard',
            'vis_descripcion' => $validated['vis_descripcion'] ?? null,
            'vis_orden' => $validated['vis_orden'] ?? 10,
            'vis_es_menu' => $validated['vis_es_menu'] ?? true,
            'vis_activo' => $validated['vis_activo'] ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'vis_id');

        // Otorgar acceso automático al SuperAdmin (Rol ID 1)
        DB::table('sec_rol_vistas')->insertOrIgnore([
            'rxv_rol_id' => 1,
            'rxv_vista_id' => $visId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::log('VIEW_CREATED', 'sec_vistas', (string)$visId, [
            'codigo' => $validated['vis_codigo'],
            'nombre' => $validated['vis_nombre'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vista creada exitosamente e incorporada al catálogo.',
            'id' => $visId,
        ], 201);
    }

    /**
     * Actualizar una Vista existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'vis_nombre' => 'required|string|max:100',
            'vis_categoria' => 'required|string|in:ADMINISTRACION,OPERACION,REPORTES,SEGURIDAD',
            'vis_icono' => 'nullable|string|max:40',
            'vis_descripcion' => 'nullable|string',
            'vis_orden' => 'nullable|integer',
            'vis_es_menu' => 'boolean',
            'vis_activo' => 'boolean',
        ]);

        DB::table('sec_vistas')
            ->where('vis_id', $id)
            ->update([
                'vis_nombre' => trim($validated['vis_nombre']),
                'vis_categoria' => $validated['vis_categoria'],
                'vis_icono' => $validated['vis_icono'] ?? 'LayoutDashboard',
                'vis_descripcion' => $validated['vis_descripcion'] ?? null,
                'vis_orden' => $validated['vis_orden'] ?? 10,
                'vis_es_menu' => $validated['vis_es_menu'] ?? true,
                'vis_activo' => $validated['vis_activo'] ?? true,
                'updated_at' => now(),
            ]);

        AuditLogger::log('VIEW_UPDATED', 'sec_vistas', (string)$id, [
            'nombre' => $validated['vis_nombre'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vista actualizada correctamente.',
        ]);
    }

    /**
     * Eliminar o desactivar una Vista
     */
    public function destroy(int $id): JsonResponse
    {
        DB::table('sec_vistas')->where('vis_id', $id)->delete();
        DB::table('sec_rol_vistas')->where('rxv_vista_id', $id)->delete();

        AuditLogger::log('VIEW_DELETED', 'sec_vistas', (string)$id, []);

        return response()->json([
            'success' => true,
            'message' => 'Vista eliminada del catálogo.',
        ]);
    }

}
