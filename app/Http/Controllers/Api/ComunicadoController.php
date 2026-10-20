<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Operations\Comunicado;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ComunicadoController extends Controller
{
    /**
     * Listar comunicados activos para la urbanización
     */
    public function index(Request $request): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $query = Comunicado::with('autor:usr_id,usr_name')
            ->where('com_urbanizacion_id', $urbanizacionId)
            ->where('com_activo', true)
            ->where(function ($q) {
                $q->whereNull('com_fecha_expiracion')
                  ->orWhere('com_fecha_expiracion', '>=', now()->toDateString());
            })
            ->orderBy('com_fijado', 'desc')
            ->orderBy('com_fecha_publicacion', 'desc');

        if ($request->filled('categoria') && $request->categoria !== 'TODAS') {
            $query->where('com_categoria', $request->categoria);
        }

        if ($request->filled('buscar')) {
            $b = '%' . strtolower($request->buscar) . '%';
            $query->where(function ($q) use ($b) {
                $q->whereRaw('LOWER(com_titulo) LIKE ?', [$b])
                  ->orWhereRaw('LOWER(com_contenido) LIKE ?', [$b]);
            });
        }

        return response()->json([
            'success' => true,
            'data' => $query->get()
        ]);
    }

    /**
     * Crear un nuevo comunicado oficial (Administración)
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $validated = $request->validate([
            'com_titulo' => 'required|string|max:200',
            'com_contenido' => 'required|string',
            'com_categoria' => 'required|in:GENERAL,MANTENIMIENTO,SEGURIDAD,ASAMBLEA,URGENTE',
            'com_prioridad' => 'required|in:BAJA,NORMAL,ALTA,URGENTE',
            'com_fecha_publicacion' => 'nullable|date',
            'com_fecha_expiracion' => 'nullable|date|after_or_equal:com_fecha_publicacion',
            'com_fijado' => 'boolean',
        ]);

        $uuid = (string) Str::uuid();

        $comunicado = Comunicado::create([
            'com_id' => $uuid,
            'com_urbanizacion_id' => $urbanizacionId,
            'com_autor_usr_id' => $user->usr_id,
            'com_titulo' => $validated['com_titulo'],
            'com_contenido' => $validated['com_contenido'],
            'com_categoria' => $validated['com_categoria'],
            'com_prioridad' => $validated['com_prioridad'],
            'com_fecha_publicacion' => $validated['com_fecha_publicacion'] ?? now()->toDateString(),
            'com_fecha_expiracion' => $validated['com_fecha_expiracion'] ?? null,
            'com_fijado' => $validated['com_fijado'] ?? false,
            'com_activo' => true,
        ]);

        AuditLogger::log('COMUNICADO_CREATED', 'op_comunicados', $uuid, [
            'titulo' => $comunicado->com_titulo,
            'categoria' => $comunicado->com_categoria,
            'prioridad' => $comunicado->com_prioridad
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comunicado publicado exitosamente.',
            'data' => $comunicado->load('autor:usr_id,usr_name')
        ], 201);
    }

    /**
     * Actualizar comunicado
     */
    public function update(Request $request, $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $comunicado = Comunicado::where('com_urbanizacion_id', $urbanizacionId)
            ->findOrFail($id);

        $validated = $request->validate([
            'com_titulo' => 'sometimes|required|string|max:200',
            'com_contenido' => 'sometimes|required|string',
            'com_categoria' => 'sometimes|required|in:GENERAL,MANTENIMIENTO,SEGURIDAD,ASAMBLEA,URGENTE',
            'com_prioridad' => 'sometimes|required|in:BAJA,NORMAL,ALTA,URGENTE',
            'com_fecha_expiracion' => 'nullable|date',
            'com_fijado' => 'boolean',
            'com_activo' => 'boolean',
        ]);

        $comunicado->update($validated);

        AuditLogger::log('COMUNICADO_UPDATED', 'op_comunicados', $id, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Comunicado actualizado exitosamente.',
            'data' => $comunicado->load('autor:usr_id,usr_name')
        ]);
    }

    /**
     * Eliminar comunicado
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $urbanizacionId = session('active_urbanizacion_id') ?: $request->header('X-Urbanizacion-Id');

        $comunicado = Comunicado::where('com_urbanizacion_id', $urbanizacionId)
            ->findOrFail($id);

        $comunicado->delete();

        AuditLogger::log('COMUNICADO_DELETED', 'op_comunicados', $id, [
            'titulo' => $comunicado->com_titulo
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comunicado eliminado exitosamente.'
        ]);
    }
}
